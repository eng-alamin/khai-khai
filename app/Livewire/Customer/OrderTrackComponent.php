<?php

namespace App\Livewire\Customer;

use Livewire\Component;

class OrderTrackComponent extends Component
{
    public string $orderId = '';

    // In a real app: fetched from DB via $orderId
    public array $order = [
        'id'         => 'KK2602',
        'restaurant' => "Maa's Kitchen",
        'status'     => 'on_the_way',
        'statusLabel'=> 'On the Way',
        'eta'        => '12 minutes',
    ];

    public array $rider = [
        'name'    => 'Karim Mia',
        'zone'    => 'Dhaka Metro',
        'vehicle' => 'Honda CB-125',
        'rating'  => 4.9,
        'photo'   => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&q=80',
    ];

    // Each step: status key, icon, label, time, done/current/pending
    public array $steps = [
        [
            'key'     => 'confirmed',
            'icon'    => 'fa-check',
            'label'   => 'Order Confirmed',
            'time'    => '3:45 PM',
            'state'   => 'done',
        ],
        [
            'key'     => 'preparing',
            'icon'    => 'fa-check',
            'label'   => 'Preparing Food',
            'time'    => '3:50 PM',
            'state'   => 'done',
        ],
        [
            'key'     => 'on_the_way',
            'icon'    => 'fa-motorcycle',
            'label'   => 'Rider on the Way',
            'time'    => '4:05 PM',
            'state'   => 'current',
        ],
        [
            'key'     => 'delivered',
            'icon'    => 'fa-home',
            'label'   => 'Delivered',
            'time'    => 'Est. 4:17 PM',
            'state'   => '',
        ],
    ];

    public function mount(string $orderId = ''): void
    {
        $this->orderId = $orderId;
        // TODO: load order, rider, steps from DB using $orderId
    }

    public function callRider(): void
    {
        // TODO: trigger call via telephony API
        $this->dispatch('show-toast', message: '📞 Calling rider...', type: 'info');
    }

    public function messageRider(): void
    {
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