<?php

namespace Tests\Feature;

use App\Models\AdminSetting;
use App\Models\Notification;
use App\Models\Order;
use App\Models\RiderProfile;
use App\Services\OrderTransitionService;
use App\Services\ZoneDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplaceData;
use Tests\TestCase;

/**
 * Zone-based dispatch rules (ZoneDispatchService) and the ready_at stamp.
 */
class ZoneDispatchTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMarketplaceData;

    private function service(): ZoneDispatchService
    {
        return app(ZoneDispatchService::class);
    }

    private function setWindow(int $minutes): void
    {
        AdminSetting::set(ZoneDispatchService::SETTING, (string) $minutes);
    }

    private function zonedRider(?string $zone): RiderProfile
    {
        $rider = $this->makeRider();
        RiderProfile::where('user_id', $rider->id)->update(['zone' => $zone]);

        return RiderProfile::where('user_id', $rider->id)->first();
    }

    private function readyOrder(string $city = 'Dhaka', ?int $minutesAgo = 0): Order
    {
        $restaurant = $this->makeRestaurant(['city' => $city]);

        return $this->makeOrder($restaurant, $this->makeUser('customer'), 'ready', [
            'ready_at' => $minutesAgo === null ? null : now()->subMinutes($minutesAgo),
        ]);
    }

    public function test_the_feature_is_off_by_default_so_every_rider_can_take_every_order(): void
    {
        $order   = $this->readyOrder('Chittagong');
        $profile = $this->zonedRider('Gazipur');

        $this->assertFalse($this->service()->isEnabled());
        $this->assertTrue($this->service()->canTake($order, $profile));
    }

    public function test_an_own_zone_rider_can_take_the_order_at_once(): void
    {
        $this->setWindow(5);
        $order = $this->readyOrder('Dhaka');

        // All three Dhaka zones cover a Dhaka restaurant.
        foreach (['Dhaka-Metro', 'Dhaka-North', 'Dhaka-South'] as $zone) {
            $this->assertTrue($this->service()->canTake($order, $this->zonedRider($zone)), $zone);
        }
    }

    public function test_an_out_of_zone_rider_must_wait_for_the_window(): void
    {
        $this->setWindow(5);
        $order = $this->readyOrder('Dhaka', 2); // ready 2 minutes ago

        $this->assertFalse($this->service()->canTake($order, $this->zonedRider('Gazipur')));
    }

    public function test_the_order_opens_to_everyone_after_the_window(): void
    {
        $this->setWindow(5);
        $order = $this->readyOrder('Dhaka', 6); // ready 6 minutes ago

        $this->assertTrue($this->service()->canTake($order, $this->zonedRider('Gazipur')));
    }

    public function test_a_rider_without_a_zone_is_never_locked_out(): void
    {
        $this->setWindow(5);
        $order = $this->readyOrder('Dhaka');

        $this->assertTrue($this->service()->canTake($order, $this->zonedRider(null)));
    }

    public function test_an_unknown_zone_in_the_profile_does_not_lock_the_rider_out(): void
    {
        $this->setWindow(5);
        $order = $this->readyOrder('Dhaka');

        $this->assertTrue($this->service()->canTake($order, $this->zonedRider('Old-Free-Text-Zone')));
    }

    public function test_an_old_order_without_ready_at_is_open_to_all(): void
    {
        $this->setWindow(5);
        $order = $this->readyOrder('Dhaka', null);

        $this->assertTrue($this->service()->canTake($order, $this->zonedRider('Gazipur')));
    }

    public function test_an_admin_order_is_matched_on_the_delivery_address_city(): void
    {
        $this->setWindow(5);
        $customer = $this->makeUser('customer');

        $order = Order::forceCreate([
            'order_number'              => 'KKADMIN0001',
            'order_type'               => 'admin',
            'restaurant_id'            => null,
            'customer_id'              => $customer->id,
            'status'                   => 'ready',
            'ready_at'                 => now(),
            'subtotal'                 => 300,
            'delivery_fee'             => 49,
            'discount_amount'          => 0,
            'total_amount'             => 349,
            'payment_method'           => 'cash_on_delivery',
            'payment_status'           => 'pending',
            'delivery_address_snapshot' => ['city' => 'Sylhet', 'full_address' => 'Somewhere'],
        ])->fresh();

        $this->assertSame('Sylhet', $this->service()->orderCity($order));
        $this->assertTrue($this->service()->canTake($order, $this->zonedRider('Sylhet')));
        $this->assertFalse($this->service()->canTake($order, $this->zonedRider('Rajshahi')));
    }

    public function test_every_zone_in_the_config_maps_to_known_cities(): void
    {
        $zoneCities = config('delivery.zone_cities');

        foreach (config('delivery.zones') as $zone) {
            $this->assertArrayHasKey($zone, $zoneCities, "Zone {$zone} has no city mapping");
        }

        foreach ($zoneCities as $zone => $cities) {
            foreach ($cities as $city) {
                $this->assertContains($city, config('delivery.cities'), "{$zone} maps to unknown city {$city}");
            }
        }
    }

    public function test_marking_an_order_ready_stamps_ready_at(): void
    {
        $restaurant = $this->makeRestaurant();
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'preparing');

        $this->assertNull($order->ready_at);

        $to = app(OrderTransitionService::class)->advance(
            $order->id,
            ['restaurant_id' => $restaurant->id],
            'preparing',
            $this->makeUser('vendor'),
            'Vendor marked ready'
        );

        $this->assertSame('ready', $to);
        $this->assertNotNull($order->fresh()->ready_at);
    }

    public function test_earlier_steps_do_not_stamp_ready_at(): void
    {
        $restaurant = $this->makeRestaurant();
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'pending');

        app(OrderTransitionService::class)->advance(
            $order->id,
            ['restaurant_id' => $restaurant->id],
            'pending',
            $this->makeUser('vendor'),
            'Vendor confirmed'
        );

        $this->assertNull($order->fresh()->ready_at);
    }

    public function test_only_own_zone_riders_are_notified_while_the_window_is_open(): void
    {
        $this->setWindow(5);

        $restaurant = $this->makeRestaurant(['city' => 'Dhaka']);
        $order      = $this->makeOrder($restaurant, $this->makeUser('customer'), 'preparing');

        $inZone  = $this->makeRider();
        $outZone = $this->makeRider();
        $noZone  = $this->makeRider();
        RiderProfile::where('user_id', $inZone->id)->update(['zone' => 'Dhaka-North']);
        RiderProfile::where('user_id', $outZone->id)->update(['zone' => 'Chittagong']);

        app(OrderTransitionService::class)->advance(
            $order->id,
            ['restaurant_id' => $restaurant->id],
            'preparing',
            $this->makeUser('vendor'),
            'Vendor marked ready'
        );

        $notified = Notification::where('type', 'order_available')->pluck('user_id')->all();

        $this->assertContains($inZone->id, $notified);
        $this->assertContains($noZone->id, $notified);
        $this->assertNotContains($outZone->id, $notified);
    }
}
