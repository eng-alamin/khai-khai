<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OrderTrackingMap extends Component
{
    public Order $order;

    /**
     * Columns for the riderProfile (HasOneThrough) eager load MUST be
     * table-qualified — both `rider_profiles` and `users` have an `id`
     * column, and the join makes a bare `id` ambiguous to MySQL.
     */
    private const RIDER_PROFILE_COLUMNS = 'riderProfile:rider_profiles.id,rider_profiles.user_id,rider_profiles.current_lat,rider_profiles.current_lng,rider_profiles.location_updated_at';

    public function mount(Order $order): void
    {
        abort_unless($order->customer_id === Auth::id(), 403);

        $this->order = $order->load([
            'restaurant:id,name,latitude,longitude',
            self::RIDER_PROFILE_COLUMNS,
        ]);
    }

    #[Computed]
    public function riderLocation(): ?array
    {
        if ($this->order->rider_id === null || $this->order->status !== 'picked_up') {
            return null;
        }

        $profile = $this->order->riderProfile;

        if (!$profile?->current_lat) {
            return null;
        }

        return [
            'lat' => (float) $profile->current_lat,
            'lng' => (float) $profile->current_lng,
            'updated_at' => $profile->location_updated_at?->diffForHumans(),
        ];
    }

    public function render()
    {
        // wire:poll প্রতি রিফ্রেশে rider এর updated location আনার জন্য fresh load
        $this->order->refresh();
        $this->order->load(self::RIDER_PROFILE_COLUMNS);

        $destination = $this->order->delivery_address_snapshot;

        return view('livewire.customer.order-tracking-map', [
            'restaurant' => $this->order->restaurant,
            'destLat' => $destination['latitude'] ?? null,
            'destLng' => $destination['longitude'] ?? null,
        ]);
    }
}