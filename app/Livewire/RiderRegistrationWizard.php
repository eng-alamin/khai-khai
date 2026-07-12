<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\RiderProfile;
use App\Mail\NewRiderRegisteredMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
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
    public string $email    = '';
    public string $phone    = '';
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
        'Bicycle'       => '🚲',
        'Motorcycle'    => '🏍️',
        'Electric Bike' => '⚡',
        'CNG'           => '🛺',
        'Car'           => '🚗',
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
                'email'                 => 'required|email|max:150|unique:users,email',
                'phone'                 => 'nullable|string|regex:/^01[3-9]\d{8}$/|unique:users,phone',
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
                'email.required'      => 'Email address is required.',
                'email.email'         => 'Please enter a valid email address.',
                'email.unique'        => 'This email is already in use.',
                'phone.regex'         => 'Please enter a valid Bangladeshi mobile number (01XXXXXXXXX).',
                'phone.unique'        => 'This mobile number is already registered.',
                'password.min'        => 'Password must be at least 8 characters.',
                'password.confirmed'  => 'Passwords do not match.',
            ],
            2 => [
                'vehicle_type.required' => 'Please select a vehicle type.',
                'vehicle_plate.unique'  => 'This vehicle plate number is already registered.',
                'license_number.unique' => 'This license number is already registered.',
                'nid_number.unique'     => 'This NID number is already registered.',
            ],
            3 => [
                'zone.required' => 'Please select a delivery zone.',
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
        $riderProfile = null;
        $user         = null;

        DB::transaction(function () use (&$riderProfile, &$user) {

            // 1. Create user
            $user = User::create([
                'uuid'        => Str::uuid(),
                'name'        => $this->name,
                'email'       => $this->email,
                'phone'       => $this->phone ?: null,
                'password'    => $this->password,
                'role'        => 'rider',
                'is_verified' => false,
                'is_active'   => true,
                'points'      => 0,
            ]);

            // 2. Create rider profile
            $riderProfile = RiderProfile::create([
                'user_id'           => $user->id,
                'vehicle_type'      => $this->vehicle_type,
                'vehicle_plate'     => $this->vehicle_plate ?: null,
                'license_number'    => $this->license_number ?: null,
                'nid_number'        => $this->nid_number ?: null,
                'zone'              => $this->zone,
                'is_online'         => false,
                'is_approved'       => false,
                'total_deliveries'  => 0,
            ]);

            if (function_exists('activity')) {
                activity()
                    ->causedBy($user)
                    ->performedOn($riderProfile)
                    ->log('rider_registered');
            }
        });

        // Notify all admins by email (outside the transaction, non-blocking failures)
        $this->notifyAdmins($riderProfile, $user);

        $this->redirect(route('rider.registration.success', ['riderProfile' => $riderProfile->id]), navigate: true);
    }

    /**
     * Send a new-rider notification email to every active admin user.
     */
    protected function notifyAdmins(?RiderProfile $riderProfile, ?User $user): void
    {
        if (! $riderProfile || ! $user) {
            return;
        }

        try {
            $adminEmails = User::query()
                ->where('role', 'admin')
                ->whereNotNull('email')
                ->pluck('email');

            if ($adminEmails->isNotEmpty()) {
                Mail::to($adminEmails->all())->send(new NewRiderRegisteredMail($riderProfile, $user));
            }
        } catch (\Throwable $e) {
            // Never block rider registration if email sending fails
            Log::error('Failed to send new rider registration email: ' . $e->getMessage());
        }
    }

    // ── Render ───────────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.rider-registration-wizard')
            ->layout('layouts.guest');
    }
}