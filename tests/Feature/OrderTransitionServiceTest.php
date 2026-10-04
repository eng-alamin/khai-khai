<?php

namespace Tests\Feature;

use App\Models\OrderRiderAssignment;
use App\Models\PlatformTransaction;
use App\Models\RiderEarning;
use App\Services\OrderTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplaceData;
use Tests\TestCase;

/**
 * Race-safe order changes and the rider assignment history.
 */
class OrderTransitionServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMarketplaceData;

    private function service(): OrderTransitionService
    {
        return app(OrderTransitionService::class);
    }

    public function test_staff_removing_a_rider_is_recorded_as_removed_by_staff(): void
    {
        $restaurant = $this->makeRestaurant();
        $rider      = $this->makeRider();
        $admin      = $this->makeUser('admin');
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'ready', ['rider' => $rider]);

        $this->assertTrue($this->service()->unassignRider($order->id, $admin, 'Rider is far away'));

        $order->refresh();
        $this->assertNull($order->rider_id);
        $this->assertSame('ready', $order->status);

        $row = OrderRiderAssignment::where('order_id', $order->id)->sole();
        $this->assertSame(OrderRiderAssignment::OUTCOME_REMOVED_BY_STAFF, $row->outcome);
        $this->assertSame($admin->id, (int) $row->ended_by);
    }

    public function test_the_pickup_timeout_is_recorded_as_released_by_system(): void
    {
        $restaurant = $this->makeRestaurant();
        $rider      = $this->makeRider();
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'ready', ['rider' => $rider]);

        // actor = null means the scheduled timeout, not a person.
        $this->assertTrue($this->service()->unassignRider($order->id, null, 'Rider did not pick up in time'));

        $row = OrderRiderAssignment::where('order_id', $order->id)->sole();
        $this->assertSame(OrderRiderAssignment::OUTCOME_RELEASED_BY_SYSTEM, $row->outcome);
        $this->assertNull($row->ended_by);
    }

    public function test_a_picked_up_order_cannot_be_unassigned(): void
    {
        $restaurant = $this->makeRestaurant();
        $rider      = $this->makeRider();
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'picked_up', ['rider' => $rider]);

        $this->assertFalse($this->service()->unassignRider($order->id, $this->makeUser('admin'), 'x'));

        $this->assertSame($rider->id, (int) $order->fresh()->rider_id);
        $this->assertSame(
            OrderRiderAssignment::OUTCOME_ACTIVE,
            OrderRiderAssignment::where('order_id', $order->id)->value('outcome')
        );
    }

    public function test_failing_a_delivery_cancels_the_order_and_creates_no_money(): void
    {
        $restaurant = $this->makeRestaurant();
        $rider      = $this->makeRider();
        $admin      = $this->makeUser('admin');
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'picked_up', ['rider' => $rider]);

        $this->assertTrue($this->service()->failDelivery($order->id, $admin, 'Customer refused'));

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('pending', $order->payment_status); // cash was never collected
        $this->assertStringStartsWith('Delivery failed:', (string) $order->cancel_reason);

        $row = OrderRiderAssignment::where('order_id', $order->id)->sole();
        $this->assertSame(OrderRiderAssignment::OUTCOME_DELIVERY_FAILED, $row->outcome);

        $this->assertSame(0, RiderEarning::count());
        $this->assertSame(0, PlatformTransaction::count());
    }

    public function test_failing_a_delivery_twice_does_nothing_the_second_time(): void
    {
        $restaurant = $this->makeRestaurant();
        $rider      = $this->makeRider();
        $admin      = $this->makeUser('admin');
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'picked_up', ['rider' => $rider]);

        $this->assertTrue($this->service()->failDelivery($order->id, $admin, 'First'));
        $this->assertFalse($this->service()->failDelivery($order->id, $admin, 'Second'));

        $this->assertSame(1, OrderRiderAssignment::where('order_id', $order->id)->count());
    }

    public function test_a_customer_cannot_cancel_an_order_a_rider_already_holds(): void
    {
        $restaurant = $this->makeRestaurant();
        $customer   = $this->makeUser('customer');
        $rider      = $this->makeRider();
        $order      = $this->makeOrder($restaurant, $customer, 'ready', ['rider' => $rider]);

        $cancelled = $this->service()->cancel(
            orderId: $order->id,
            scope: ['customer_id' => $customer->id],
            allowedFrom: ['pending', 'confirmed', 'preparing', 'ready'],
            actor: $customer,
            reason: 'Changed my mind',
            logNote: 'Customer cancelled',
            activityText: 'Customer cancelled order'
        );

        $this->assertFalse($cancelled);
        $this->assertSame('ready', $order->fresh()->status);
    }

    public function test_cancel_needs_the_right_owner(): void
    {
        $restaurant = $this->makeRestaurant();
        $customer   = $this->makeUser('customer');
        $stranger   = $this->makeUser('customer');
        $order      = $this->makeOrder($restaurant, $customer, 'pending');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service()->cancel(
            orderId: $order->id,
            scope: ['customer_id' => $stranger->id], // not this order's customer
            allowedFrom: ['pending'],
            actor: $stranger,
            reason: 'x',
            logNote: 'x',
            activityText: 'x'
        );
    }

    public function test_advance_moves_one_step_and_a_double_click_cannot_move_two(): void
    {
        $restaurant = $this->makeRestaurant();
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'pending');
        $vendor     = $this->makeUser('vendor');
        $scope      = ['restaurant_id' => $restaurant->id];

        $first  = $this->service()->advance($order->id, $scope, 'pending', $vendor, 'Vendor confirmed');
        $second = $this->service()->advance($order->id, $scope, 'pending', $vendor, 'Vendor confirmed'); // stale screen

        $this->assertSame('confirmed', $first);
        $this->assertNull($second);
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_the_vendor_cannot_advance_an_order_a_rider_holds(): void
    {
        $restaurant = $this->makeRestaurant();
        $rider      = $this->makeRider();
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'preparing', ['rider' => $rider]);

        $result = $this->service()->advance(
            $order->id,
            ['restaurant_id' => $restaurant->id],
            'preparing',
            $this->makeUser('vendor'),
            'Vendor marked ready'
        );

        $this->assertNull($result);
        $this->assertSame('preparing', $order->fresh()->status);
    }
}
