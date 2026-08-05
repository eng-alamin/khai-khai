<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\AdminSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SettingComponent extends Component
{
    // ── General Settings Fields ─────────────────────────────
    public string $platformName  = '';
    public string $supportEmail  = '';
    public string $helpline      = '';

    // ── Delivery Charge Fields (displayed in Taka, stored in Paisa) ──
    public float $minimumDeliveryCharge = 40;
    public float $perKmDeliveryRate     = 10;
    public float $includedKmInMinimum   = 1;

    // ── Commission Settings Fields ───────────────────────────
    public float  $defaultCommissionRate = 12;
    public float  $premiumVendorRate     = 8;
    public string $paymentCycle          = 'monthly';

    private array $generalKeyMap = [
        'platformName' => 'platform_name',
        'supportEmail' => 'support_email',
        'helpline'     => 'helpline',
    ];

    private array $deliveryKeyMap = [
        'minimumDeliveryCharge' => 'minimum_delivery_charge',
        'perKmDeliveryRate'     => 'per_km_delivery_rate',
        'includedKmInMinimum'   => 'included_km_in_minimum',
    ];

    private array $commissionKeyMap = [
        'defaultCommissionRate' => 'default_commission_rate',
        'premiumVendorRate'     => 'premium_vendor_rate',
        'paymentCycle'          => 'payment_cycle',
    ];

    // ── Mount: load existing settings ───────────────────────
    public function mount(): void
    {
        $settings = AdminSetting::whereIn('key', [
            ...array_values($this->generalKeyMap),
            ...array_values($this->deliveryKeyMap),
            ...array_values($this->commissionKeyMap),
            'sms_otp_enabled',
        ])->pluck('value', 'key');

        $this->platformName  = $settings->get('platform_name', 'KhaiKhai Food Delivery');
        $this->supportEmail  = $settings->get('support_email', 'support@khaikhai.com.bd');
        $this->helpline      = $settings->get('helpline', '09612-KHAI (5424)');
        $this->smsOtpEnabled = filter_var($settings->get('sms_otp_enabled', true), FILTER_VALIDATE_BOOLEAN);

        // Paisa (stored) → Taka (displayed)
        $this->minimumDeliveryCharge = ((int) $settings->get('minimum_delivery_charge', 4000)) / 100;
        $this->perKmDeliveryRate     = ((int) $settings->get('per_km_delivery_rate', 1000)) / 100;
        $this->includedKmInMinimum   = (float) $settings->get('included_km_in_minimum', 1);

        // Commission — stored as plain percentage numbers, no conversion needed
        $this->defaultCommissionRate = (float) $settings->get('default_commission_rate', 12);
        $this->premiumVendorRate     = (float) $settings->get('premium_vendor_rate', 8);
        $this->paymentCycle          = $settings->get('payment_cycle', 'monthly');
    }

    // ── Validation ───────────────────────────────────────────
    protected function generalRules(): array
    {
        return [
            'platformName' => ['required', 'string', 'max:150'],
            'supportEmail' => ['required', 'email', 'max:150'],
            'helpline'     => ['required', 'string', 'max:50'],
        ];
    }

    protected function deliveryRules(): array
    {
        return [
            'minimumDeliveryCharge' => ['required', 'numeric', 'min:0', 'max:1000'],
            'perKmDeliveryRate'     => ['required', 'numeric', 'min:0', 'max:500'],
            'includedKmInMinimum'   => ['required', 'numeric', 'min:0', 'max:50'],
        ];
    }

    protected function commissionRules(): array
    {
        return [
            'defaultCommissionRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'premiumVendorRate'     => ['required', 'numeric', 'min:0', 'max:100'],
            'paymentCycle'          => ['required', 'string', 'in:daily,weekly,monthly'],
        ];
    }

    protected array $generalMessages = [
        'platformName.required' => 'Platform name is required.',
        'supportEmail.required' => 'Support email is required.',
        'supportEmail.email'    => 'Please enter a valid email.',
        'helpline.required'     => 'Helpline number is required.',
    ];

    protected array $deliveryMessages = [
        'minimumDeliveryCharge.required' => 'Minimum delivery charge is required.',
        'minimumDeliveryCharge.numeric'  => 'Minimum delivery charge must be a number.',
        'perKmDeliveryRate.required'     => 'Per-km delivery rate is required.',
        'perKmDeliveryRate.numeric'      => 'Per-km delivery rate must be a number.',
        'includedKmInMinimum.required'   => 'Included distance is required.',
        'includedKmInMinimum.numeric'    => 'Included distance must be a number.',
    ];

    protected array $commissionMessages = [
        'defaultCommissionRate.required' => 'Default commission rate is required.',
        'defaultCommissionRate.numeric'  => 'Default commission rate must be a number.',
        'defaultCommissionRate.max'      => 'Commission rate cannot exceed 100%.',
        'premiumVendorRate.required'     => 'Premium vendor rate is required.',
        'premiumVendorRate.numeric'      => 'Premium vendor rate must be a number.',
        'premiumVendorRate.max'          => 'Premium vendor rate cannot exceed 100%.',
        'paymentCycle.required'          => 'Payment cycle is required.',
        'paymentCycle.in'                => 'Payment cycle must be daily, weekly, or monthly.',
    ];

    // ── Save: General Settings ───────────────────────────────
    public function saveGeneral(): void
    {
        $validated = $this->validate($this->generalRules(), $this->generalMessages);

        try {
            DB::beginTransaction();

            foreach ($this->generalKeyMap as $property => $key) {
                AdminSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value'      => (string) $validated[$property],
                        'updated_by' => Auth::id(),
                    ]
                );
                Cache::forget("admin_setting:{$key}");
            }

            activity()
                ->causedBy(Auth::user())
                ->withProperties($validated)
                ->log('General settings updated');

            DB::commit();

            session()->flash('success_general', 'General settings saved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('General settings save failed', [
                'admin_id' => Auth::id(),
                'error'    => $e->getMessage(),
            ]);
            session()->flash('error_general', 'Failed to save general settings. Please try again.');
        }
    }

    // ── Save: Delivery Charge Settings ───────────────────────
    public function saveDeliverySettings(): void
    {
        $validated = $this->validate($this->deliveryRules(), $this->deliveryMessages);

        try {
            DB::beginTransaction();

            AdminSetting::updateOrCreate(
                ['key' => 'minimum_delivery_charge'],
                [
                    'value'      => (string) (int) round($validated['minimumDeliveryCharge'] * 100),
                    'updated_by' => Auth::id(),
                ]
            );
            Cache::forget('admin_setting:minimum_delivery_charge');

            AdminSetting::updateOrCreate(
                ['key' => 'per_km_delivery_rate'],
                [
                    'value'      => (string) (int) round($validated['perKmDeliveryRate'] * 100),
                    'updated_by' => Auth::id(),
                ]
            );
            Cache::forget('admin_setting:per_km_delivery_rate');

            AdminSetting::updateOrCreate(
                ['key' => 'included_km_in_minimum'],
                [
                    'value'      => (string) $validated['includedKmInMinimum'],
                    'updated_by' => Auth::id(),
                ]
            );
            Cache::forget('admin_setting:included_km_in_minimum');

            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'minimum_delivery_charge' => $validated['minimumDeliveryCharge'],
                    'per_km_delivery_rate'    => $validated['perKmDeliveryRate'],
                    'included_km_in_minimum'  => $validated['includedKmInMinimum'],
                ])
                ->log('Delivery charge settings updated');

            DB::commit();

            session()->flash('success_delivery', 'Delivery charge settings saved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Delivery settings save failed', [
                'admin_id' => Auth::id(),
                'error'    => $e->getMessage(),
            ]);
            session()->flash('error_delivery', 'Failed to save delivery settings. Please try again.');
        }
    }

    // ── Save: Commission Settings ────────────────────────────
    public function saveCommissionSettings(): void
    {
        $validated = $this->validate($this->commissionRules(), $this->commissionMessages);

        try {
            DB::beginTransaction();

            foreach ($this->commissionKeyMap as $property => $key) {
                AdminSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value'      => (string) $validated[$property],
                        'updated_by' => Auth::id(),
                    ]
                );
                Cache::forget("admin_setting:{$key}");
            }

            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'default_commission_rate' => $validated['defaultCommissionRate'],
                    'premium_vendor_rate'     => $validated['premiumVendorRate'],
                    'payment_cycle'           => $validated['paymentCycle'],
                ])
                ->log('Commission settings updated');

            DB::commit();

            session()->flash('success_commission', 'Commission settings saved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Commission settings save failed', [
                'admin_id' => Auth::id(),
                'error'    => $e->getMessage(),
            ]);
            session()->flash('error_commission', 'Failed to save commission settings. Please try again.');
        }
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.admin.setting-component')
            ->layout('layouts.admin', [
                'title'           => 'Settings | KhaiKhai',
                'breadcrumbTitle' => 'Settings',
            ]);
    }
}