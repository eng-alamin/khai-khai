<?php

namespace App\Livewire\Customer;

use Livewire\Component;

class AddressComponent extends Component
{
    public function render()
    {
        return view('livewire.customer.address-component')
            ->layout('layouts.customer', [
                    'title' => 'Address | KhaiKhai', 'breadcrumbTitle' => 'Address'
                ]);
    }
}
