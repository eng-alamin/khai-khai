<?php

namespace App\Livewire\Rider;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\RiderProfile;

class SettingComponent extends Component
{
    // User fields
    public string $name   = '';
    public string $role   = '';
    public string $avatar = '';
    public string $email  = '';
    public string $phone  = '';

    // Rider profile fields (from rider_profiles table)
    public ?int    $riderProfileId = null;
    public string  $vehicleType    = '';
    public ?string $vehiclePlate   = null;
    public ?string $licenseNumber  = null;
    public ?string $nidNumber      = null;
    public ?string $zone           = null;

    // Current location (map picker) — maps to rider_profiles.current_lat / current_lng
    public ?float $currentLat = null;
    public ?float $currentLng = null;

    // Stats (read-only, shown on UI)
    public ?float $avgRating       = null;
    public int    $totalDeliveries = 0;
    public bool   $isOnline        = false;
    public bool   $isApproved      = false;

    protected function rules(): array
    {
        $userId = Auth::id();

        return [
            'phone'         => "required|string|max:15|unique:users,phone,{$userId}",
            'email'         => "nullable|email|max:150|unique:users,email,{$userId}",
            'vehicleType'   => 'required|string|max:40',
            'vehiclePlate'  => ['nullable', 'string', 'max:20', Rule::unique('rider_profiles', 'vehicle_plate')->ignore($this->riderProfileId)],
            'licenseNumber' => ['nullable', 'string', 'max:30', Rule::unique('rider_profiles', 'license_number')->ignore($this->riderProfileId)],
            'nidNumber'     => ['nullable', 'string', 'max:20', Rule::unique('rider_profiles', 'nid_number')->ignore($this->riderProfileId)],
            'zone'          => 'nullable|string|max:80',
            'currentLat'    => 'nullable|numeric|between:-90,90',
            'currentLng'    => 'nullable|numeric|between:-180,180',
        ];
    }

    protected function messages(): array
    {
        return [
            'phone.unique' => 'This phone number is already registered with another account.',
            'email.unique' => 'This email is already registered with another account.',
        ];
    }

    public function mount(): void
    {
        $user = Auth::user();

        $this->name   = $user->name;
        $this->role   = ucfirst($user->role);
        $this->avatar = $user->avatar
            ? $user->avatar
            : strtoupper(substr($user->name, 0, 1));
        $this->email  = $user->email ?? '';
        $this->phone  = $user->phone ?? '';

        // Rider profile stats & info
        $profile = $user->riderProfile;

        if ($profile) {
            $this->riderProfileId  = $profile->id;
            $this->vehicleType     = $profile->vehicle_type;
            $this->vehiclePlate    = $profile->vehicle_plate;
            $this->licenseNumber   = $profile->license_number;
            $this->nidNumber       = $profile->nid_number;
            $this->zone            = $profile->zone;
            $this->currentLat      = $profile->current_lat !== null ? (float) $profile->current_lat : null;
            $this->currentLng      = $profile->current_lng !== null ? (float) $profile->current_lng : null;
            $this->avgRating       = $profile->avg_rating;
            $this->totalDeliveries = $profile->total_deliveries;
            $this->isOnline        = (bool) $profile->is_online;
            $this->isApproved      = (bool) $profile->is_approved;
        }
    }

    /* ── Clear validation errors when the map picker sets coordinates ── */
    public function updatedCurrentLat($value): void
    {
        $this->resetErrorBag('currentLat');
    }

    public function updatedCurrentLng($value): void
    {
        $this->resetErrorBag('currentLng');
    }

    public function saveSetting(): void
    {
        $this->validate();

        $user = Auth::user();

        \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
            // Update user
            $user->update([
                'email' => $this->email ?: null,
                'phone' => $this->phone,
            ]);

            // Update or create rider profile
            $data = [
                'vehicle_type'   => $this->vehicleType,
                'vehicle_plate'  => $this->vehiclePlate ?: null,
                'license_number' => $this->licenseNumber ?: null,
                'nid_number'     => $this->nidNumber ?: null,
                'zone'           => $this->zone ?: null,
                'current_lat'    => $this->currentLat,
                'current_lng'    => $this->currentLng,
                'location_updated_at' => ($this->currentLat !== null && $this->currentLng !== null) ? now() : null,
            ];

            if ($this->riderProfileId) {
                RiderProfile::where('id', $this->riderProfileId)->update($data);
            } else {
                $profile = $user->riderProfile()->create($data);
                $this->riderProfileId = $profile->id;
            }

            activity()->causedBy($user)->performedOn($user)->log('Rider updated profile');
        });

        $this->dispatch('show-toast', message: 'Setting updated ✅', type: 'success');
    }

    public function toggleOnlineStatus(): void
    {
        if (! $this->riderProfileId) {
            $this->dispatch('show-toast', message: 'Profile not set up yet', type: 'warning');
            return;
        }

        if (! $this->isApproved) {
            $this->dispatch('show-toast', message: 'Account not approved yet', type: 'warning');
            return;
        }

        $this->isOnline = ! $this->isOnline;

        RiderProfile::where('id', $this->riderProfileId)->update([
            'is_online' => $this->isOnline,
        ]);

        $this->dispatch(
            'show-toast',
            message: $this->isOnline ? 'You are now Online 🟢' : 'You are now Offline 🔴',
            type: 'success'
        );
    }

    public function render()
    {
        return view('livewire.rider.setting-component')
            ->layout('layouts.rider', [
                'title'           => 'Settings | KhaiKhai',
                'breadcrumbTitle' => 'Settings',
            ]);
    }
}