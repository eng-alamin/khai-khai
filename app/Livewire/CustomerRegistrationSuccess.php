<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class CustomerRegistrationSuccess extends Component
{
    public User $user;

    /**
     * Laravel resolves {user} via route-model-binding.
     * We defensively make sure only a genuine customer account can be
     * shown on this page (never a vendor/rider/admin account).
     */
    public function mount(User $user): void
    {
        abort_unless($user->role === 'customer', 404);

        $this->user = $user;
    }

    public function render()
    {
        return view('livewire.customer-registration-success')
            ->layout('layouts.guest');
    }
}
