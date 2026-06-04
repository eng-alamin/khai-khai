<?php

namespace App\Livewire\Customer;

use Livewire\Component;

class OrderListComponent extends Component
{
    public string $activeFilter = 'সব';

    public array $filters = ['সব', 'চলমান', 'ডেলিভারড', 'বাতিল'];

    // In a real app this comes from: Auth::user()->orders()->latest()->get()
    public array $orders = [
        [
            'id'          => '#KK2601',
            'rest'        => "মা'র রান্নাঘর",
            'items'       => 'ভাত + মুরগির তরকারি',
            'time'        => 'আজ ৩:৪৫ PM',
            'total'       => 220,
            'status'      => 'delivered',
            'statusLabel' => 'ডেলিভারড',
        ],
        [
            'id'          => '#KK2599',
            'rest'        => 'City Burger House',
            'items'       => 'Chicken Burger × 2',
            'time'        => 'গতকাল ৭:৩০ PM',
            'total'       => 380,
            'status'      => 'cancelled',
            'statusLabel' => 'বাতিল',
        ],
        [
            'id'          => '#KK2598',
            'rest'        => 'Dhaka Biryani',
            'items'       => 'মুরগির বিরিয়ানি',
            'time'        => '২ দিন আগে',
            'total'       => 180,
            'status'      => 'delivered',
            'statusLabel' => 'ডেলিভারড',
        ],
    ];

    public function setFilter(string $filter): void
    {
        $this->activeFilter = $filter;
    }

    public function getFilteredOrdersProperty(): array
    {
        if ($this->activeFilter === 'সব') {
            return $this->orders;
        }

        $map = [
            'চলমান'   => 'active',
            'ডেলিভারড' => 'delivered',
            'বাতিল'   => 'cancelled',
        ];

        $key = $map[$this->activeFilter] ?? null;

        return $key
            ? array_values(array_filter($this->orders, fn($o) => $o['status'] === $key))
            : $this->orders;
    }

    public function reorder(string $orderId): void
    {
        // TODO: pre-fill cart with previous order items
        $this->dispatch('show-toast', message: 'পুনরায় অর্ডার দেওয়া হচ্ছে...', type: 'info');
        $this->dispatch('navigate-to', page: 'menu');
    }

    public function submitReview(string $orderId): void
    {
        // TODO: open review modal
        $this->dispatch('show-toast', message: 'রিভিউ দেওয়ার জন্য ধন্যবাদ! ⭐', type: 'success');
    }

    public function render()
    {
        return view('livewire.customer.order-list-component', [
                 'filteredOrders' => $this->filteredOrders,
            ])
            ->layout('layouts.customer', [
                'title' => 'Orders | KhaiKhai', 'breadcrumbTitle' => 'Orders'
            ]);
    }
}