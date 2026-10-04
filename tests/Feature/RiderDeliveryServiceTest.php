<?php

namespace Tests\Feature;

use App\Models\OrderRiderAssignment;
use App\Models\PlatformTransaction;
use App\Models\RiderEarning;
use App\Services\RiderDeliveryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplaceData;
use Tests\TestCase;

/**
 * The money written when a rider completes a delivery.
 */
class RiderDeliveryServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMarketplaceData;

    public function test_completing_a_delivery_writes_every_money_record_once(): void
    {
        $restaurant = $this->makeRestaurant(['commission_rate' => 10]);
        $customer   = $this->makeUser('customer');
        $rider      = $this->makeRider();
        $order      = $this->makeOrder($restaurant, $customer, 'picked_up', ['rider' => $rider]);

        app(RiderDeliveryService::class)->completeDelivery($order->id, $rider);

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertNotNull($order->delivered_at);
        // Cash on delivery: the rider collected the cash.
        $this->assertSame('paid', $order->payment_status);

        // Rider earning = the delivery fee.
        $earning = RiderEarning::where('order_id', $order->id)->sole();
        $this->assertSame($rider->id, (int) $earning->rider_id);
        $this->assertEquals(40.00, (float) $earning->amount);

        // Commission 10% of the food subtotal (500): 50 / vendor 450 / rider 40.
        $txn = PlatformTransaction::where('order_id', $order->id)->sole();
        $this->assertEquals(10.00, (float) $txn->commission_rate);
        $this->assertEquals(50.00, (float) $txn->platform_commission);
        $this->assertEquals(450.00, (float) $txn->vendor_amount);
        $this->assertEquals(40.00, (float) $txn->rider_amount);
        $this->assertSame('cod', $txn->gateway);

        // History row closed as delivered.
        $assignment = OrderRiderAssignment::where('order_id', $order->id)->sole();
        $this->assertSame(OrderRiderAssignment::OUTCOME_DELIVERED, $assignment->outcome);
        $this->assertNotNull($assignment->ended_at);
    }

    public function test_a_second_complete_call_cannot_create_duplicate_money(): void
    {
        $restaurant = $this->makeRestaurant();
        $rider      = $this->makeRider();
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'picked_up', ['rider' => $rider]);

        $service = app(RiderDeliveryService::class);
        $service->completeDelivery($order->id, $rider);

        try {
            $service->completeDelivery($order->id, $rider); // double click
            $this->fail('The second call should not find a picked-up order.');
        } catch (ModelNotFoundException) {
            // expected
        }

        $this->assertSame(1, RiderEarning::where('order_id', $order->id)->count());
        $this->assertSame(1, PlatformTransaction::where('order_id', $order->id)->count());
    }

    public function test_another_rider_cannot_complete_the_order(): void
    {
        $restaurant = $this->makeRestaurant();
        $owner      = $this->makeRider();
        $intruder   = $this->makeRider();
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'picked_up', ['rider' => $owner]);

        $this->expectException(ModelNotFoundException::class);

        app(RiderDeliveryService::class)->completeDelivery($order->id, $intruder);
    }

    public function test_an_order_not_yet_picked_up_cannot_be_completed(): void
    {
        $restaurant = $this->makeRestaurant();
        $rider      = $this->makeRider();
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'ready', ['rider' => $rider]);

        try {
            app(RiderDeliveryService::class)->completeDelivery($order->id, $rider);
            $this->fail('A "ready" order must not be completable.');
        } catch (ModelNotFoundException) {
            // expected
        }

        $this->assertSame(0, RiderEarning::count());
        $this->assertSame(0, PlatformTransaction::count());
        $this->assertSame('ready', $order->fresh()->status);
    }

    public function test_online_payment_order_keeps_its_payment_status_and_gateway(): void
    {
        $restaurant = $this->makeRestaurant();
        $rider      = $this->makeRider();
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'picked_up', [
            'rider'          => $rider,
            'payment_method' => 'bkash',
            'payment_status' => 'paid',
        ]);

        app(RiderDeliveryService::class)->completeDelivery($order->id, $rider);

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('bkash', PlatformTransaction::where('order_id', $order->id)->value('gateway'));
    }
}
