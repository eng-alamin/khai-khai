<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerRegistrationComponent extends Component
{
    // ── Account info (Single Step) ────────────────────────────
    public string $name     = '';
    public string $phone    = '';
    public string $email    = '';
    public string $password = '';
    public string $password_confirmation = '';

    // ── Validation rules ──────────────────────────────────────
    protected function rules(): array
    {
        return [
            'name'                  => 'required|string|min:3|max:100',
            'phone'                 => 'nullable|string|regex:/^01[3-9]\d{8}$/|unique:users,phone',
            'email'                 => 'required|email|max:150|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required'       => 'Name is required.',
            'phone.regex'         => 'Please enter a valid Bangladeshi mobile number (01XXXXXXXXX).',
            'phone.unique'        => 'This mobile number is already registered.',
            'email.required'      => 'Email is required.',
            'email.email'         => 'Please enter a valid email address.',
            'email.unique'        => 'This email is already in use.',
            'password.min'        => 'Password must be at least 8 characters.',
            'password.confirmed'  => 'Passwords do not match.',
        ];
    }

    // ── Submit ────────────────────────────────────────────────
    public function submit(): void
    {
        $this->validate($this->rules(), $this->messages());

        DB::transaction(function () {
            User::create([
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
        });

        $this->redirect(route('customer.registration.success'), navigate: true);
    }

    public function render()
    {
        return view('livewire.customer-registration-component')
            ->layout('layouts.guest');
    }
}