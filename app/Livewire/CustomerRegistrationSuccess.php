<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class CustomerRegistrationSuccess extends Component
{
    public User $user;

    /**
     * Laravel resolves {user} via route-model-binding.
     *
     * Only the browser session that just registered this customer may see
     * the page (same rule as the rider and vendor success pages). Without
     * this check anyone could open /registration/success/1, /2, /3 ... and
     * read other customers' names.
     */
    public function mount(User $user): void
    {
        $expectedId = session('just_registered_customer_id');

        abort_unless(
            $expectedId !== null
                && (int) $expectedId === (int) $user->id
                && $user->role === 'customer',
            403
        );

        $this->user = $user;

        // one-time view: prevent re-access via back button / bookmark / link sharing
        session()->forget('just_registered_customer_id');
    }

    public function render()
    {
        return view('livewire.customer-registration-success')
            ->layout('layouts.guest');
    }
}
