<?php

namespace App\Livewire;

use App\Models\RiderProfile;
use Livewire\Component;

class RiderRegistrationSuccess extends Component
{
    public ?RiderProfile $riderProfile = null;

    /**
     * Laravel resolves {riderProfile} via route-model-binding and injects
     * the RiderProfile model directly. We re-load it here with the
     * 'user' relation eager-loaded to avoid an extra query in the view.
     */
    public function mount(RiderProfile $riderProfile): void
    {
        $this->riderProfile = $riderProfile->loadMissing('user');
    }

    public function render()
    {
        return view('livewire.rider-registration-success')
            ->layout('layouts.guest');
    }
}