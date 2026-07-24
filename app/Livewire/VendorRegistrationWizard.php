<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\MenuCategory;
use App\Mail\NewVendorRegisteredMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class VendorRegistrationWizard extends Component
{
    use WithFileUploads;

    // ── Wizard state ────────────────────────────────────────────────
    public int $currentStep = 1;
    public int $totalSteps  = 3;

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
    public string $description     = '';

    // ── Step 3 : Media & branding ────────────────────────────────────
    public $logo   = null;
    public $banner = null;

    // ── Categories list ──────────────────────────────────────────────
    public array $categories = [
        'Bangla Food',
        'Fast Food',
        'Burger',
        'Pizza',
        'Biryani',
        'Chinese',
        'Indian',
        'Seafood',
        'Dessert & Sweets',
        'Tea & Coffee',
        'Healthy Food',
        'Street Food',
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
                'email'                 => 'required|email|max:150|unique:users,email',
                'phone'                 => 'nullable|string|regex:/^01[3-9]\d{8}$/|unique:users,phone',
                'password'              => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required',
            ],
            2 => [
                'restaurant_name'   => 'required|string|min:3|max:120',
                'category'          => 'required|string',
                'restaurant_phone'  => 'required|string|regex:/^01[3-9]\d{8}$/',
                'address'           => 'required|string|min:10|max:255',
                'city'              => 'required|string',
                'description'       => 'nullable|string|max:500',
            ],
            3 => [
                'logo'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            ],
            default => [],
        };
    }

    protected function messagesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'email.required'  => 'Email address is required.',
                'email.email'     => 'Please enter a valid email address.',
                'email.unique'    => 'This email is already registered.',
                'phone.regex'     => 'Please enter a valid Bangladeshi mobile number (01XXXXXXXXX).',
                'phone.unique'    => 'This mobile number is already registered.',
                'password.min'    => 'Password must be at least 8 characters.',
                'password.confirmed' => 'Passwords do not match.',
            ],
            2 => [
                'restaurant_phone.regex' => 'Please enter a valid Bangladeshi mobile number.',
                'address.min'            => 'Please enter a complete address (at least 10 characters).',
            ],
            3 => [
                'logo.max'   => 'Logo must not exceed 2 MB.',
                'banner.max' => 'Banner must not exceed 4 MB.',
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
            $this->rulesForStep(3),
            $this->messagesForStep(3)
        );

        $restaurant = null;
        $user       = null;

        DB::transaction(function () use (&$restaurant, &$user) {

            // 1. Create user
            $user = User::create([
                'uuid'        => Str::uuid(),
                'name'        => $this->name,
                'phone'       => $this->phone ?: null,
                'email'       => $this->email,
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

            // 3. Create restaurant (fields matched to actual restaurants migration)
            $restaurant = Restaurant::create([
                'owner_id'        => $user->id,
                'name'            => $this->restaurant_name,
                'slug'            => $this->generateUniqueSlug($this->restaurant_name),
                'category'        => $this->category,
                'emoji'           => null,
                'logo_url'        => $logoPath ? Storage::url($logoPath) : null,
                'banner_url'      => $bannerPath ? Storage::url($bannerPath) : null,
                'address'         => $this->address,
                'city'            => $this->city,
                'latitude'        => null,
                'longitude'       => null,
                'phone'           => $this->restaurant_phone,
                'avg_rating'      => null,
                'total_reviews'   => 0,
                'tag'             => null,
                'commission_rate' => 15.00,
                'is_open'         => false,
                'is_approved'     => false,
                'is_active'       => true,
            ]);

            // 4. Default menu categories
            $categories = [
                ['name' => 'Burger', 'emoji' => '🍔'],
                ['name' => 'Pizza', 'emoji' => '🍕'],
                ['name' => 'Fried Chicken', 'emoji' => '🍗'],
                ['name' => 'Biryani', 'emoji' => '🍛'],
                ['name' => 'Drinks', 'emoji' => '🥤'],
                ['name' => 'Dessert', 'emoji' => '🍰'],
            ];

            foreach ($categories as $index => $cat) {
                MenuCategory::firstOrCreate(
                    [
                        'restaurant_id' => $restaurant->id,
                        'name'          => $cat['name'],
                    ],
                    [
                        'emoji'      => $cat['emoji'],
                        'sort_order' => $index + 1,
                        'is_active'  => true,
                    ]
                );
            }

            if (function_exists('activity')) {
                activity()
                    ->causedBy($user)
                    ->performedOn($restaurant)
                    ->log('vendor_registered');
            }
        });

        // Notify all admins by email (outside the DB transaction, non-blocking failures)
        $this->notifyAdmins($restaurant, $user);

        // Redirect
        $this->redirect(route('vendor.registration.success', ['restaurant' => $restaurant->id]), navigate: true);
    }

    /**
     * Generate a unique slug for the restaurant, resolving collisions.
     */
    protected function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (Restaurant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /**
     * Send a new-vendor notification email to every active admin user.
     */
    protected function notifyAdmins(?Restaurant $restaurant, ?User $user): void
    {
        if (! $restaurant || ! $user) {
            return;
        }

        try {
            $adminEmails = User::query()
                ->where('role', 'admin')
                ->whereNotNull('email')
                ->pluck('email');

            if ($adminEmails->isNotEmpty()) {
                Mail::to($adminEmails->all())->send(new NewVendorRegisteredMail($restaurant, $user));
            }
        } catch (\Throwable $e) {
            // Never block vendor registration if email sending fails
            Log::error('Failed to send new vendor registration email: ' . $e->getMessage());
        }
    }

    // ── Render ───────────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.vendor-registration-wizard')
            ->layout('layouts.guest');
    }
}