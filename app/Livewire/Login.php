<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Login extends Component
{
    // ── State ────────────────────────────────────────────
    public string $email    = '';
    public string $password = '';
    public bool   $remember = false;
    public string $errorMsg = '';
    public bool   $loading  = false;

    // ── Validation rules ─────────────────────────────────
    protected function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required'    => 'Please enter your email address.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Please enter your password.',
            'password.min'      => 'Password must be at least 6 characters.',
        ];
    }

    // ── Login action ──────────────────────────────────────
    public function login(): void
    {
        $this->errorMsg = '';
        $this->validate();

        // Find user by email
        $user = User::where('email', $this->email)->first();

        if (! $user) {
            $this->errorMsg = 'No account found with this email.';
            return;
        }

        if (! $user->is_active) {
            $this->errorMsg = 'Your account has been deactivated. Please contact support.';
            return;
        }

        // Check password
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            // Success
        } else {
            $this->errorMsg = 'Incorrect password.';
            return;
        }

        // Vendor: check approval status
        // if ($user->isVendor() && $user->restaurant && ! $user->restaurant->is_approved) {
        //     $this->errorMsg = 'Your restaurant has not been approved yet. Please wait.';
        //     return;
        // }

        // Log in
        Auth::login($user, $this->remember);

        // Role-based redirect
        $this->redirectBasedOnRole($user);
    }

    // ── Role redirect ─────────────────────────────────────
    private function redirectBasedOnRole(User $user): void
    {
        $route = match ($user->role) {
            'vendor'   => 'vendor.dashboard',
            'rider'    => 'rider.dashboard',
            'admin'    => 'admin.dashboard',
            default    => 'customer.home',   // customer
        };

        $this->redirect(route($route), navigate: true);
    }

    public function render()
    {
        return view('livewire.login')
            ->layout('layouts.guest');
    }
}