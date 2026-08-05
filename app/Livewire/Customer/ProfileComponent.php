<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomerAddress;

class ProfileComponent extends Component
{
    // User fields
    public string $name   = '';
    public string $role   = '';
    public string $avatar = '';
    public string $email  = '';
    public string $phone  = '';

    // Stats from customer_profiles
    public int    $totalOrders = 0;
    public int    $points      = 0;
    public ?float $avgRating   = null;
    public bool   $isVerified  = false;

    // Address fields (from default customer_address)
    public ?int   $defaultAddressId = null;
    public string $fullAddress      = '';
    public string $city             = 'Dhaka';
    public string $postalCode       = '';
    public string $addressLabel     = 'home';

    protected function rules(): array
    {
        $userId = Auth::id();

        return [
            'phone'       => "required|string|max:15|unique:users,phone,{$userId}",
            'email'       => "nullable|email|max:150|unique:users,email,{$userId}",
            'fullAddress' => 'nullable|string|max:500',
            'city'        => 'nullable|string|max:60',
            'postalCode'  => 'nullable|string|max:10',
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
        $this->phone  = $user->phone;
        $this->points = $user->points;
        $this->isVerified = (bool) $user->is_verified;

        // Customer profile stats 
        $profile          = $user->customerProfile;
        $this->totalOrders = $profile?->total_orders ?? 0;
        $this->avgRating   = $profile?->avg_rating_given;

        // Default address
        $defaultAddr = $user->addresses()->where('is_default', true)->first();
        if ($defaultAddr) {
            $this->defaultAddressId = $defaultAddr->id;
            $this->fullAddress      = $defaultAddr->full_address;
            $this->city             = $defaultAddr->city;
            $this->postalCode       = $defaultAddr->postal_code ?? '';
            $this->addressLabel     = $defaultAddr->label;
        }
    }

    public function saveProfile(): void
    {
        $this->validate();

        $user = Auth::user();

        \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
            // Update user
            $user->update([
                'email' => $this->email ?: null,
                'phone' => $this->phone,
            ]);

            // Update or create default address
            if ($this->fullAddress) {
                if ($this->defaultAddressId) {
                    CustomerAddress::where('id', $this->defaultAddressId)->update([
                        'full_address' => $this->fullAddress,
                        'city'         => $this->city,
                        'postal_code'  => $this->postalCode ?: null,
                    ]);
                } else {
                    // Clear any existing defaults first
                    $user->addresses()->update(['is_default' => false]);

                    $addr = $user->addresses()->create([
                        'label'        => $this->addressLabel,
                        'full_address' => $this->fullAddress,
                        'city'         => $this->city,
                        'postal_code'  => $this->postalCode ?: null,
                        'is_default'   => true,
                    ]);

                    $this->defaultAddressId = $addr->id;
                }
            }

            activity()->causedBy($user)->performedOn($user)->log('Customer updated profile');
        });

        $this->dispatch('show-toast', message: 'Profile updated ✅', type: 'success');
    }

    public function changePassword(): void
    {
        $this->dispatch('show-toast', message: 'Change Password', type: 'info');
        // TODO: $this->redirect(route('customer.change-password'));
    }

    public function notifications(): void
    {
        $this->dispatch('show-toast', message: 'Notification Settings', type: 'info');
        // TODO: $this->redirect(route('customer.notifications'));
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(route('login'), navigate: true);
    }

    public function render()
    {
        return view('livewire.customer.profile-component')
            ->layout('layouts.customer', [
                'title'           => 'Profile | KhaiKhai',
                'breadcrumbTitle' => 'My Profile',
            ]);
    }
}