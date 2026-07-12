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

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->statusMsg = 'A password reset link has been sent to your email address.';
            $this->reset('email');
        } else {
            $this->errorMsg = 'We could not find an account with that email address.';
        }
    }

    public function render()
    {
        return view('livewire.forgot-password')
            ->layout('layouts.guest');
    }
}