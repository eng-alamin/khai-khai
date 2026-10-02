<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Livewire\Concerns\HasAccountRegistrationValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerRegistrationComponent extends Component
{
    use HasAccountRegistrationValidation;

    // ── Account info (Single Step) ────────────────────────────
    public string $name     = '';
    public string $phone    = '';
    public string $email    = '';
    public string $password = '';
    public string $password_confirmation = '';

    // ── Validation rules ──────────────────────────────────────
    protected function rules(): array
    {
        return $this->accountRegistrationRules();
    }

    protected function messages(): array
    {
        return $this->accountRegistrationMessages();
    }

    // ── Submit ────────────────────────────────────────────────
    public function submit(): void
    {
        $this->validate($this->rules(), $this->messages());

        $user = DB::transaction(function () {
            $user = User::create([
                'uuid'        => Str::uuid(),
                'name'        => $this->name,
                'phone'       => $this->phone ?: null,
                'email'       => $this->email,
                'password'    => $this->password,
                'role'        => 'customer',
                'is_verified' => false,
                'is_active'   => true,
                'points'      => 0,
            ]);

            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->log('Customer account registered');

            return $user;
        });

        // Lets the success page confirm that this browser just registered this user.
        session(['just_registered_customer_id' => $user->id]);

        $this->redirect(route('customer.registration.success', ['user' => $user->id]), navigate: true);
    }

    public function render()
    {
        return view('livewire.customer-registration-component')
            ->layout('layouts.guest');
    }
}