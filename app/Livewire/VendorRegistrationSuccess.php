<?php

namespace App\Livewire;

use App\Models\Restaurant;
use Livewire\Component;

class VendorRegistrationSuccess extends Component
{
    public ?Restaurant $restaurant = null;

    public function mount(Restaurant $restaurant): void
    {
        $expectedId = session('just_registered_restaurant_id');

        abort_unless(
            $expectedId !== null && (int) $expectedId === (int) $restaurant->id,
            403
        );

        $this->restaurant = $restaurant->loadMissing('owner');

        // one-time view: prevent re-access via back button / bookmark / link sharing
        session()->forget('just_registered_restaurant_id');
    }

    public function render()
    {
        return view('livewire.vendor-registration-success')
            ->layout('layouts.guest');
    }
}