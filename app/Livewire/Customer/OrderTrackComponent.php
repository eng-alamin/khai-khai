<?php

namespace App\Livewire\Customer;

use Livewire\Component;

class OrderTrackComponent extends Component
{
    public function render()
    {
        return view('livewire.customer.order-track-component')
            ->layout('layouts.customer', [
                'title' => 'Orders | KhaiKhai', 'breadcrumbTitle' => 'Orders'
            ]);
    }
}
