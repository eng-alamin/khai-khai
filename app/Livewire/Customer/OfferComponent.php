<?php

namespace App\Livewire\Customer;

use Livewire\Component;

class OfferComponent extends Component
{
    public function render()
    {
        return view('livewire.customer.offer-component')
            ->layout('layouts.customer', [
                    'title' => 'Offer | KhaiKhai', 'breadcrumbTitle' => 'Offer'
                ]);
    }
}
