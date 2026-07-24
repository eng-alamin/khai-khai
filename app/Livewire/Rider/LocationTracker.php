<?php

declare(strict_types=1);

namespace App\Livewire\Rider;

use App\Models\RiderProfile;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LocationTracker extends Component
{
    public function updateLocation(float $latitude, float $longitude): void
    {
        $this->validateCoordinates($latitude, $longitude);

        $profile = RiderProfile::where('user_id', Auth::id())->first();

        abort_unless($profile !== null, 404, 'Rider profile not found.');

        if (!$profile->is_online) {
            return;
        }

        $profile->update([
            'current_lat' => $latitude,
            'current_lng' => $longitude,
            'location_updated_at' => now(),
        ]);
    }

    private function validateCoordinates(float $lat, float $lng): void
    {
        abort_unless(
            $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180,
            422,
            'Invalid coordinates.'
        );
    }

    public function render()
    {
        return view('livewire.rider.location-tracker');
    }
}