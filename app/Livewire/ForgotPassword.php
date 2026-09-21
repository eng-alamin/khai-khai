<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Password;

class ForgotPassword extends Component
{
    // ── State ────────────────────────────────────────────
    public string $email     = '';
    public string $statusMsg = '';
    public string $errorMsg  = '';

    // ── Validation rules ─────────────────────────────────
    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => 'Please enter your email address.',
            'email.email'    => 'Please enter a valid email address.',
        ];
    }

    // ── Send reset link ───────────────────────────────────
    public function sendResetLink(): void
    {
        $this->statusMsg = '';
        $this->errorMsg  = '';

        $this->validate();

        // Always attempt — but never reveal whether the account exists.
        Password::sendResetLink(['email' => $this->email]);

        // Same message regardless of outcome (prevents email enumeration).
        $this->statusMsg = 'If an account exists with that email address, a password reset link has been sent.';
        $this->reset('email');
    }

    public function render()
    {
        return view('livewire.forgot-password')
            ->layout('layouts.guest');
    }
}