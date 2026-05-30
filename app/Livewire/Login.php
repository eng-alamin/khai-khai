<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Login extends Component
{
    // ── State ────────────────────────────────────────────
    public string $phone    = '';
    public string $password = '';
    public bool   $remember = false;
    public string $errorMsg = '';
    public bool   $loading  = false;
 
    // ── Validation rules ─────────────────────────────────
    protected function rules(): array
    {
        return [
            'phone'    => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'password' => ['required', 'min:6'],
        ];
    }
 
    protected function messages(): array
    {
        return [
            'phone.required' => 'মোবাইল নম্বর দিন।',
            'phone.regex'    => 'সঠিক বাংলাদেশি নম্বর দিন (01XXXXXXXXX)।',
            'password.required' => 'পাসওয়ার্ড দিন।',
            'password.min'      => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে।',
        ];
    }
 
    // ── Login action ──────────────────────────────────────
    public function login(): void
    { 
        $this->errorMsg = '';
        $this->validate();
 
        // Find user by phone
        $user = User::where('phone', $this->phone)->first();
 
        if (! $user) {
            $this->errorMsg = 'এই নম্বরে কোনো অ্যাকাউন্ট নেই।';
            return;
        }
 
        if (! $user->is_active) {
            $this->errorMsg = 'আপনার অ্যাকাউন্ট নিষ্ক্রিয় করা হয়েছে। সাপোর্টে যোগাযোগ করুন।';
            return;
        }
 
        // Check password (stored as password_hash)
        if (Auth::attempt(['phone' => $this->phone, 'password' => $this->password])) {
            // Success
        } else {
            $this->errorMsg = 'পাসওয়ার্ড ভুল হয়েছে।';
        }

        // Vendor: check approval status
        // if ($user->isVendor() && $user->restaurant && ! $user->restaurant->is_approved) {
        //     $this->errorMsg = 'আপনার রেস্টুরেন্ট এখনো অনুমোদিত হয়নি। অনুগ্রহ করে অপেক্ষা করুন।';
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
