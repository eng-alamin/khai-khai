<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\VendorSetting;
use App\Models\MenuCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

class VendorRegistrationWizard extends Component
{
     use WithFileUploads;
 
    // ── Wizard state ────────────────────────────────────────────────
    public int $currentStep = 1;
    public int $totalSteps  = 4;
 
    // ── Step 1 : Personal / Account info ────────────────────────────
    public string $name     = '';
    public string $phone    = '';
    public string $email    = '';
    public string $password = '';
    public string $password_confirmation = '';
 
    // ── Step 2 : Restaurant info ─────────────────────────────────────
    public string $restaurant_name = '';
    public string $category        = '';
    public string $restaurant_phone = '';
    public string $address         = '';
    public string $city            = '';
    public string $postal_code     = '';
    public string $description     = '';
 
    // ── Step 3 : Media & branding ────────────────────────────────────
    public $logo   = null;
    public $banner = null;
 
    // ── Step 4 : Delivery settings ───────────────────────────────────
    public int    $delivery_fee     = 49;       // BDT (will store as paisa)
    public int    $avg_delivery_min = 20;
    public int    $avg_delivery_max = 40;
    public int    $min_order_amount = 100;      // BDT
    public int    $prep_time_min    = 15;
    public bool   $auto_accept      = false;
 
    // ── Categories list ──────────────────────────────────────────────
    public array $categories = [
        'বাংলা খাবার',
        'ফাস্টফুড',
        'বার্গার',
        'পিৎজা',
        'বিরিয়ানি',
        'চাইনিজ',
        'ইন্ডিয়ান',
        'সী-ফুড',
        'ডেজার্ট ও মিষ্টি',
        'চা ও কফি',
        'হেলদি ফুড',
        'স্ট্রিট ফুড',
    ];
 
    // ── Cities list ──────────────────────────────────────────────────
    public array $cities = [
        'Dhaka', 'Gazipur', 'Narayanganj', 'Chittagong',
        'Sylhet', 'Rajshahi', 'Khulna', 'Barisal', 'Mymensingh',
    ];
 
    // ── Validation rules per step ────────────────────────────────────
    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'name'                  => 'required|string|min:3|max:100',
                'phone'                 => 'required|string|regex:/^01[3-9]\d{8}$/|unique:users,phone',
                'email'                 => 'nullable|email|max:150|unique:users,email',
                'password'              => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required',
            ],
            2 => [
                'restaurant_name'   => 'required|string|min:3|max:120',
                'category'          => 'required|string',
                'restaurant_phone'  => 'required|string|regex:/^01[3-9]\d{8}$/',
                'address'           => 'required|string|min:10|max:255',
                'city'              => 'required|string',
                'postal_code'       => 'nullable|digits_between:4,10',
                'description'       => 'nullable|string|max:500',
            ],
            3 => [
                'logo'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            ],
            4 => [
                'delivery_fee'     => 'required|integer|min:0|max:1000',
                'avg_delivery_min' => 'required|integer|min:5|max:120',
                'avg_delivery_max' => 'required|integer|min:5|max:180|gte:avg_delivery_min',
                'min_order_amount' => 'required|integer|min:0',
                'prep_time_min'    => 'required|integer|min:5|max:120',
            ],
            default => [],
        };
    }
 
    protected function messagesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'phone.regex'    => 'সঠিক বাংলাদেশি মোবাইল নম্বর দিন (01XXXXXXXXX)।',
                'phone.unique'   => 'এই মোবাইল নম্বরটি ইতিমধ্যে নিবন্ধিত।',
                'email.unique'   => 'এই ইমেইলটি ইতিমধ্যে ব্যবহৃত হচ্ছে।',
                'password.min'   => 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষর হতে হবে।',
                'password.confirmed' => 'পাসওয়ার্ড মিলছে না।',
            ],
            2 => [
                'restaurant_phone.regex' => 'সঠিক বাংলাদেশি মোবাইল নম্বর দিন।',
                'address.min'            => 'পূর্ণ ঠিকানা লিখুন (কমপক্ষে ১০ অক্ষর)।',
            ],
            3 => [
                'logo.max'   => 'লোগো সর্বোচ্চ ২ MB হতে পারবে।',
                'banner.max' => 'ব্যানার সর্বোচ্চ ৪ MB হতে পারবে।',
            ],
            4 => [
                'avg_delivery_max.gte' => 'সর্বোচ্চ সময় সর্বনিম্ন সময়ের চেয়ে বেশি হতে হবে।',
            ],
            default => [],
        };
    }
 
    // ── Navigation ───────────────────────────────────────────────────
    public function nextStep(): void
    {
        $this->validate(
            $this->rulesForStep($this->currentStep),
            $this->messagesForStep($this->currentStep)
        );
 
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }
 
    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }
 
    public function goToStep(int $step): void
    {
        // Only allow going back to already-visited steps
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
        }
    }
 
    // ── Final submission ─────────────────────────────────────────────
    public function submit(): void
    {
        $this->validate(
            $this->rulesForStep(4),
            $this->messagesForStep(4)
        );

        DB::transaction(function () {

            // 1. Create user
            $user = User::create([
                'uuid'        => Str::uuid(),
                'name'        => $this->name,
                'phone'       => $this->phone,
                'email'       => $this->email ?: null,
                'password'    => $this->password,
                'role'        => 'vendor',
                'is_verified' => false,
                'is_active'   => true,
                'points'      => 0,
            ]);

            // 2. Upload files
            $logoPath = $this->logo
                ? $this->logo->store('restaurants/logos', 'public')
                : null;

            $bannerPath = $this->banner
                ? $this->banner->store('restaurants/banners', 'public')
                : null;

            // 3. Create restaurant
            $restaurant = Restaurant::create([
                'owner_id'         => $user->id,
                'name'             => $this->restaurant_name,
                'slug'             => Str::slug($this->restaurant_name) . '-' . Str::lower(Str::random(4)),
                'category'         => $this->category,
                'phone'            => $this->restaurant_phone,
                'address'          => $this->address,
                'city'             => $this->city,
                'logo_url'         => $logoPath ? Storage::url($logoPath) : null,
                'banner_url'       => $bannerPath ? Storage::url($bannerPath) : null,
                'delivery_fee'     => $this->delivery_fee * 100,
                'avg_delivery_min' => $this->avg_delivery_min,
                'avg_delivery_max' => $this->avg_delivery_max,
                'commission_rate'  => 15.00,
                'is_open'          => false,
                'is_approved'      => false,
                'is_active'        => true,
            ]);

            // 4. DEFAULT MENU CATEGORIES CREATE (IMPORTANT)
            $categories = [
                ['name' => 'Burger', 'emoji' => '🍔'],
                ['name' => 'Pizza', 'emoji' => '🍕'],
                ['name' => 'Fried Chicken', 'emoji' => '🍗'],
                ['name' => 'Biryani', 'emoji' => '🍛'],
                ['name' => 'Drinks', 'emoji' => '🥤'],
                ['name' => 'Dessert', 'emoji' => '🍰'],
            ];

            foreach ($categories as $index => $cat) {
                \App\Models\MenuCategory::firstOrCreate(
                    [
                        'restaurant_id' => $restaurant->id,
                        'name' => $cat['name'],
                    ],
                    [
                        'emoji'      => $cat['emoji'],
                        'sort_order' => $index + 1,
                        'is_active'  => true,
                    ]
                );
            }

            // 5. Vendor settings
            VendorSetting::create([
                'restaurant_id'      => $restaurant->id,
                'auto_accept'        => $this->auto_accept,
                'prep_time_min'      => $this->prep_time_min,
                'notification_sound' => true,
                'min_order_amount'   => $this->min_order_amount * 100,
            ]);
        });

        // Redirect
        $this->redirect(route('vendor.registration.success'), navigate: true);
    }
 
    // ── Render ───────────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.vendor-registration-wizard')
            ->layout('layouts.guest');
    }
}
