<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

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

        $throttleKey = Str::lower($this->email).'|'.request()->ip();

        // ── Rate limit: block after too many failed attempts ──
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->errorMsg = "অনেকবার ভুল চেষ্টা করা হয়েছে। {$seconds} সেকেন্ড পর আবার চেষ্টা করুন।";
            return;
        }

        // ── Attempt credentials (single query, no user enumeration) ──
        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);
            $this->errorMsg = 'ইমেইল অথবা পাসওয়ার্ড সঠিক নয়।';
            return;
        }

        // Credentials matched — now check account-level gates.
        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            $this->errorMsg = 'আপনার অ্যাকাউন্ট নিষ্ক্রিয় করা হয়েছে। সাপোর্টে যোগাযোগ করুন।';
            return;
        }

        // Vendor: check approval status
        if ($user->isVendor() && $user->restaurant && ! $user->restaurant->is_approved) {
            Auth::logout();
            $this->errorMsg = 'আপনার রেস্টুরেন্ট এখনো অনুমোদিত হয়নি। অনুগ্রহ করে অপেক্ষা করুন।';
            return;
        }

        RateLimiter::clear($throttleKey);

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