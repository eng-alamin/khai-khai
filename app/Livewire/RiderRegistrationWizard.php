<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\RiderProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class RiderRegistrationWizard extends Component
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

    // ── Step 2 : Vehicle & Documents ────────────────────────────────
    public string $vehicle_type   = '';
    public string $vehicle_plate  = '';
    public string $license_number = '';
    public string $nid_number     = '';

    // ── Step 3 : Zone & Availability ────────────────────────────────
    public string $zone = '';

    // ── Step 4 : Review & Submit ─────────────────────────────────────
    // (No extra fields — summary only)

    // ── Vehicle types ────────────────────────────────────────────────
    public array $vehicleTypes = [
        'বাইসাইকেল'   => '🚲',
        'মোটরসাইকেল'  => '🏍️',
        'ইলেকট্রিক বাইক' => '⚡',
        'সিএনজি'      => '🛺',
        'কার'         => '🚗',
    ];

    // ── Zones ────────────────────────────────────────────────────────
    public array $zones = [
        'Dhaka-Metro',
        'Dhaka-North',
        'Dhaka-South',
        'Gazipur',
        'Narayanganj',
        'Chittagong',
        'Sylhet',
        'Rajshahi',
        'Khulna',
        'Barisal',
        'Mymensingh',
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
                'vehicle_type'   => 'required|string',
                'vehicle_plate'  => 'nullable|string|max:20|unique:rider_profiles,vehicle_plate',
                'license_number' => 'nullable|string|max:30|unique:rider_profiles,license_number',
                'nid_number'     => 'nullable|string|max:20|unique:rider_profiles,nid_number',
            ],
            3 => [
                'zone' => 'required|string',
            ],
            4 => [],
            default => [],
        };
    }

    protected function messagesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'phone.regex'        => 'সঠিক বাংলাদেশি মোবাইল নম্বর দিন (01XXXXXXXXX)।',
                'phone.unique'       => 'এই মোবাইল নম্বরটি ইতিমধ্যে নিবন্ধিত।',
                'email.unique'       => 'এই ইমেইলটি ইতিমধ্যে ব্যবহৃত হচ্ছে।',
                'password.min'       => 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষর হতে হবে।',
                'password.confirmed' => 'পাসওয়ার্ড মিলছে না।',
            ],
            2 => [
                'vehicle_type.required'     => 'যানবাহনের ধরন নির্বাচন করুন।',
                'vehicle_plate.unique'      => 'এই নম্বর প্লেটটি ইতিমধ্যে নিবন্ধিত।',
                'license_number.unique'     => 'এই লাইসেন্স নম্বরটি ইতিমধ্যে নিবন্ধিত।',
                'nid_number.unique'         => 'এই NID নম্বরটি ইতিমধ্যে নিবন্ধিত।',
            ],
            3 => [
                'zone.required' => 'ডেলিভারি জোন নির্বাচন করুন।',
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
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    // ── Final submission ─────────────────────────────────────────────
    public function submit(): void
    {
        DB::transaction(function () {

            // 1. Create user
            $user = User::create([
                'uuid'        => Str::uuid(),
                'name'        => $this->name,
                'phone'       => $this->phone,
                'email'       => $this->email ?: null,
                'password'    => $this->password,
                'role'        => 'rider',
                'is_verified' => false,
                'is_active'   => true,
                'points'      => 0,
            ]);

            // 2. Create rider profile
            RiderProfile::create([
                'user_id'        => $user->id,
                'vehicle_type'   => $this->vehicle_type,
                'vehicle_plate'  => $this->vehicle_plate ?: null,
                'license_number' => $this->license_number ?: null,
                'nid_number'     => $this->nid_number ?: null,
                'zone'           => $this->zone,
                'is_online'      => false,
                'is_approved'    => false,
                'total_deliveries' => 0,
            ]);
        });

        $this->redirect(route('rider.dashboard'), navigate: true);
    }

    // ── Render ───────────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.rider-registration-wizard')
            ->layout('layouts.guest');
    }
}