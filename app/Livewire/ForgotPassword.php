<?php

namespace App\Livewire;

use App\Livewire\Concerns\ThrottlesActions;

use Livewire\Component;
use Illuminate\Support\Facades\Password;

class ForgotPassword extends Component
{
    use ThrottlesActions;

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

        // Limit by IP and by the typed address (stops mail bombing a person).
        // The same limit applies whether or not the account exists.
        $wait = max(
            $this->throttleHit('forgot-ip', (string) request()->ip(), 10, 900),
            $this->throttleHit('forgot-email', strtolower($this->email), 3, 900),
        );

        if ($wait > 0) {
            $this->errorMsg = $this->throttleMessage($wait);
            return;
        }

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