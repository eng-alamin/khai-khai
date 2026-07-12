<?php

namespace App\Livewire;

use App\Models\Restaurant;
use Livewire\Component;

class VendorRegistrationSuccess extends Component
{
    public ?Restaurant $restaurant = null;

    /**
     * Laravel resolves {restaurant} via route-model-binding and injects
     * the Restaurant model directly. We re-load it here with the
     * 'owner' relation eager-loaded to avoid an extra query in the view.
     */
    public function mount(Restaurant $restaurant): void
    {
        $this->restaurant = $restaurant->loadMissing('owner');
    }

    public function render()
    {
        return view('livewire.vendor-registration-success')
            ->layout('layouts.guest');
    }
}