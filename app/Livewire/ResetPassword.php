<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;

class ResetPassword extends Component
{
    // ── State ────────────────────────────────────────────
    public string $token                  = '';
    public string $email                  = '';
    public string $password               = '';
    public string $password_confirmation  = '';
    public string $errorMsg               = '';
    public string $statusMsg              = '';

    // ── Mount: capture token from route + email from query string ──
    public function mount(string $token, ?string $email = null): void
    {
        $this->token = $token;
        $this->email = $email ?? (string) request()->query('email', '');
    }

    // ── Validation rules ─────────────────────────────────
    protected function rules(): array
    {
        return [
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required'     => 'Email address is missing from the reset link.',
            'password.required'  => 'Please enter a new password.',
            'password.min'       => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    // ── Reset action ───────────────────────────────────────
    public function resetPassword(): void
    {
        $this->errorMsg  = '';
        $this->statusMsg = '';

        $this->validate();

        $status = Password::reset(
            [
                'token'                 => $this->token,
                'email'                 => $this->email,
                'password'              => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ],
            function (User $user) {
                $user->forceFill([
                    'password'       => $this->password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordResetEvent($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $this->statusMsg = 'Your password has been reset successfully. Redirecting to login...';
            $this->dispatch('password-reset-success');
            $this->redirect(route('login'), navigate: true);
        } else {
            $this->errorMsg = 'This password reset link is invalid or has expired. Please request a new one.';
        }
    }

    public function render()
    {
        return view('livewire.reset-password')
            ->layout('layouts.guest');
    }
}