<?php

namespace App\Livewire\Customer;

use Livewire\Component;

class SupportComponent extends Component
{
    public function render()
    {
        return view('livewire.customer.support-component')
        ->layout('layouts.customer', [
                    'title' => 'Offer | KhaiKhai', 'breadcrumbTitle' => 'Offer'
                ]);
    }
}
