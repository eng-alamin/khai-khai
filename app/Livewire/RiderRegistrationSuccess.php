<?php

namespace App\Livewire;

use App\Models\RiderProfile;
use Livewire\Component;

class RiderRegistrationSuccess extends Component
{
    public ?RiderProfile $riderProfile = null;

    public function mount(RiderProfile $riderProfile): void
    {
        $expectedId = session('just_registered_rider_profile_id');

        abort_unless(
            $expectedId !== null && (int) $expectedId === (int) $riderProfile->id,
            403
        );

        $this->riderProfile = $riderProfile->loadMissing('user');

        // one-time view: prevent re-access via back button / bookmark / link sharing
        session()->forget('just_registered_rider_profile_id');
    }

    public function render()
    {
        return view('livewire.rider-registration-success')
            ->layout('layouts.guest');
    }
}