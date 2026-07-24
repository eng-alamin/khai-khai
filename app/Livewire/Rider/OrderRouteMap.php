<?php

declare(strict_types=1);

namespace App\Livewire\Rider;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OrderRouteMap extends Component
{
    public Order $order;

    /**
     * Columns for the riderProfile (HasOneThrough) eager load MUST be
     * table-qualified — both `rider_profiles` and `users` have an `id`
     * column, and the join makes a bare `id` ambiguous to MySQL.
     */
    private const RIDER_PROFILE_COLUMNS = 'riderProfile:rider_profiles.id,rider_profiles.user_id,rider_profiles.current_lat,rider_profiles.current_lng,rider_profiles.location_updated_at';

    /**
     * গুরুত্বপূর্ণ: parameter-এর নাম ইচ্ছাকৃতভাবে "orderId" রাখা হয়েছে, "order" না।
     * Livewire mount() কল হওয়ার আগেই blade থেকে পাঠানো params array-এর key
     * public property-এর নামের সাথে মিলিয়ে সরাসরি assign করার চেষ্টা করে।
     * key নাম "order" হলে Livewire (int) সরাসরি $order (Order টাইপ) প্রপার্টিতে
     * বসাতে চেষ্টা করত এবং mount() চলারও আগে crash করত।
     */
    public function mount(int|string $orderId): void
    {
        $orderModel = Order::query()->findOrFail($orderId);

        // Customer-এর OrderTrackingMap-এর বিপরীত: এখানে rider নিজের অর্ডার কিনা চেক হচ্ছে
        abort_unless($orderModel->rider_id === Auth::id(), 403);

        $this->order = $orderModel->load([
            'restaurant:id,name,latitude,longitude',
            self::RIDER_PROFILE_COLUMNS,
        ]);
    }

    /**
     * Rider নিজের বর্তমান লোকেশন — অন্য কোনো rider-এর না।
     * LocationTracker কম্পোনেন্ট প্রতি ৮ সেকেন্ডে এই একই rider_profiles row আপডেট করছে।
     */
    #[Computed]
    public function myLocation(): ?array
    {
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
        // wire:poll প্রতি রিফ্রেশে নিজের updated location আনার জন্য fresh load
        $this->order->refresh();
        $this->order->load([
            'restaurant:id,name,latitude,longitude',
            self::RIDER_PROFILE_COLUMNS,
        ]);

        $destination = $this->order->delivery_address_snapshot;

        return view('livewire.rider.order-route-map', [
            'restaurant' => $this->order->restaurant,
            'destLat' => $destination['latitude'] ?? null,
            'destLng' => $destination['longitude'] ?? null,
        ]);
    }
}