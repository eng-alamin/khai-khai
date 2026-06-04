<?php

namespace App\Livewire\Customer;

use Livewire\Component;

class ProfileComponent extends Component
{
    public function render()
    {
        return view('livewire.customer.profile-component')
            ->layout('layouts.customer', [
                    'title' => 'Orders | KhaiKhai', 'breadcrumbTitle' => 'Orders'
                ]);
    }
}
