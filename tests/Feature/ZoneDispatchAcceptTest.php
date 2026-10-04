<?php

namespace Tests\Feature;

use App\Livewire\Rider\DeliveryOngoingComponent;
use App\Models\AdminSetting;
use App\Models\RiderProfile;
use App\Services\ZoneDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsMarketplaceData;
use Tests\TestCase;

/**
 * The server side of zone dispatch: a rider outside the zone cannot accept
 * an order even by calling acceptDelivery() directly.
 *
 * Kept in its own file because it renders the Livewire component.
 */
class ZoneDispatchAcceptTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMarketplaceData;

    private function readyOrder(string $city, int $minutesAgo)
    {
        $restaurant = $this->makeRestaurant(['city' => $city]);

        return $this->makeOrder($restaurant, $this->makeUser('customer'), 'ready', [
            'ready_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    public function test_an_out_of_zone_rider_cannot_accept_during_the_window(): void
    {
        AdminSetting::set(ZoneDispatchService::SETTING, '5');
        $order = $this->readyOrder('Dhaka', 1);

        $rider = $this->makeRider();
        RiderProfile::where('user_id', $rider->id)->update(['zone' => 'Chittagong']);

        Livewire::actingAs($rider)
            ->test(DeliveryOngoingComponent::class)
            ->call('acceptDelivery', $order->id);

        $this->assertNull($order->fresh()->rider_id);
    }

    public function test_the_same_rider_can_accept_after_the_window(): void
    {
        AdminSetting::set(ZoneDispatchService::SETTING, '5');
        $order = $this->readyOrder('Dhaka', 10);

        $rider = $this->makeRider();
        RiderProfile::where('user_id', $rider->id)->update(['zone' => 'Chittagong']);

        Livewire::actingAs($rider)
            ->test(DeliveryOngoingComponent::class)
            ->call('acceptDelivery', $order->id);

        $this->assertSame($rider->id, (int) $order->fresh()->rider_id);
    }

    public function test_an_own_zone_rider_can_accept_at_once(): void
    {
        AdminSetting::set(ZoneDispatchService::SETTING, '5');
        $order = $this->readyOrder('Dhaka', 0);

        $rider = $this->makeRider();
        RiderProfile::where('user_id', $rider->id)->update(['zone' => 'Dhaka-South']);

        Livewire::actingAs($rider)
            ->test(DeliveryOngoingComponent::class)
            ->call('acceptDelivery', $order->id);

        $this->assertSame($rider->id, (int) $order->fresh()->rider_id);
    }
}
