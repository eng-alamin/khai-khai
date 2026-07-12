<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\AdminSetting;
use Illuminate\Support\Facades\Auth;

class CommissionComponent extends Component
{
    // ── Form Fields ──────────────────────────────────────────
    public string $defaultCommissionRate = '';
    public string $deliveryCharge        = '';
    public string $premiumVendorRate     = '';
    public string $paymentCycle          = 'monthly';

    private array $keyMap = [
        'defaultCommissionRate' => 'default_commission_rate',
        'deliveryCharge'        => 'delivery_charge',
        'premiumVendorRate'     => 'premium_vendor_rate',
        'paymentCycle'          => 'payment_cycle',
    ];

    public array $paymentCycleOptions = [
        'daily'   => 'Daily',
        'weekly'  => 'Weekly',
        'monthly' => 'Monthly',
    ];

    // ── Mount: load existing settings ───────────────────────
    public function mount(): void
    {
        $settings = AdminSetting::whereIn('key', array_values($this->keyMap))
            ->pluck('value', 'key');

        $this->defaultCommissionRate = $settings->get('default_commission_rate', '12');
        $this->deliveryCharge        = $settings->get('delivery_charge', '49');
        $this->premiumVendorRate     = $settings->get('premium_vendor_rate', '8');
        $this->paymentCycle          = $settings->get('payment_cycle', 'monthly');
    }

    // ── Validation ───────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'defaultCommissionRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'deliveryCharge'        => ['required', 'numeric', 'min:0'],
            'premiumVendorRate'     => ['required', 'numeric', 'min:0', 'max:100'],
            'paymentCycle'          => ['required', 'in:daily,weekly,monthly'],
        ];
    }

    protected array $messages = [
        'defaultCommissionRate.required' => 'Default commission rate is required.',
        'defaultCommissionRate.numeric'  => 'Default commission rate must be a number.',
        'deliveryCharge.required'        => 'Delivery charge is required.',
        'deliveryCharge.numeric'         => 'Delivery charge must be a number.',
        'premiumVendorRate.required'     => 'Premium vendor rate is required.',
        'premiumVendorRate.numeric'      => 'Premium vendor rate must be a number.',
        'paymentCycle.required'          => 'Please select a payment cycle.',
    ];

    // ── Save ─────────────────────────────────────────────────
    public function save(): void
    {
        $validated = $this->validate();

        foreach ($this->keyMap as $property => $key) {
            AdminSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value'      => (string) $validated[$property],
                    'updated_by' => Auth::id(),
                ]
            );
        }

        session()->flash('success', 'Commission configuration saved successfully.');
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.admin.commission-component')
            ->layout('layouts.admin', [
                'title'           => 'Commission Settings | KhaiKhai',
                'breadcrumbTitle' => 'Commission Configuration',
            ]);
    }
}