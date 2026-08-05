<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OrderTrackComponent extends Component
{
    public Order $order;

    /**
     * Route param: order_number (e.g. "KK2602"), NOT the numeric id.
     * If your route actually binds by numeric id, change the query in mount()
     * from where('order_number', ...) to findOrFail($orderId).
     */
    public function mount(string $orderId): void
    {
        $order = Order::query()
            ->where('order_number', $orderId)
            ->with([
                'restaurant:id,name,latitude,longitude',
                'rider:id,name,phone',
                'rider.riderProfile',
                'statusLogs' => fn ($q) => $q->orderBy('created_at'),
            ])
            ->firstOrFail();

        abort_unless($order->customer_id === Auth::id(), 403);

        $this->order = $order;
    }

    /**
     * Step definitions for the main (non-cancelled) delivery flow.
     */
    private const FLOW = [
        'confirmed'  => ['icon' => 'fa-check',      'label' => 'Order Confirmed'],
        'preparing'  => ['icon' => 'fa-utensils',   'label' => 'Preparing Food'],
        'picked_up'  => ['icon' => 'fa-motorcycle', 'label' => 'Rider on the Way'],
        'delivered'  => ['icon' => 'fa-home',       'label' => 'Delivered'],
    ];

    #[Computed]
    public function steps(): array
    {
        $logs = $this->order->statusLogs->keyBy('to_status');
        $currentStatus = $this->order->status;

        // Order in which statuses become "done" once the current status is reached or passed.
        $order = array_keys(self::FLOW);
        $currentIndex = array_search($currentStatus, $order, true);

        $steps = [];

        foreach (self::FLOW as $key => $meta) {
            $log = $logs->get($key);
            $keyIndex = array_search($key, $order, true);

            if ($key === $currentStatus) {
                $state = 'current';
            } elseif ($currentIndex !== false && $keyIndex !== false && $keyIndex < $currentIndex) {
                $state = 'done';
            } elseif ($log !== null) {
                $state = 'done';
            } else {
                $state = '';
            }

            $steps[] = [
                'key'   => $key,
                'icon'  => $meta['icon'],
                'label' => $meta['label'],
                'time'  => $log?->created_at?->format('g:i A')
                    ?? ($state === 'current' ? $this->order->updated_at->format('g:i A') : null),
                'state' => $state,
            ];
        }

        return $steps;
    }

    #[Computed]
    public function riderInfo(): ?array
    {
        if ($this->order->rider_id === null) {
            return null;
        }

        $rider = $this->order->rider;
        $profile = $rider?->riderProfile;

        return [
            'name'    => $rider?->name ?? 'Rider',
            'zone'    => $profile->zone ?? 'N/A',
            'vehicle' => trim(($profile->vehicle_type ?? '') . ' ' . ($profile->vehicle_plate ?? '')) ?: 'N/A',
            'rating'  => $profile->avg_rating ?? null,
            'photo'   => null,
        ];
    }

    public function callRider(): void
    {
        if ($this->order->rider_id === null) {
            $this->dispatch('show-toast', message: 'কোনো rider এখনো assign হয়নি।', type: 'error');
            return;
        }

        // TODO: trigger call via telephony API
        $this->dispatch('show-toast', message: '📞 Calling rider...', type: 'info');
    }

    public function messageRider(): void
    {
        if ($this->order->rider_id === null) {
            $this->dispatch('show-toast', message: 'কোনো rider এখনো assign হয়নি।', type: 'error');
            return;
        }

        // TODO: open chat channel
        $this->dispatch('show-toast', message: '💬 Opening chat...', type: 'info');
    }

    public function render()
    {
        return view('livewire.customer.order-track-component')
            ->layout('layouts.customer', [
                'title'           => 'Track Order | KhaiKhai',
                'breadcrumbTitle' => 'Track Order',
            ]);
    }
}