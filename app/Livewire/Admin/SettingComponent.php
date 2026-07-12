<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\AdminSetting;
use Illuminate\Support\Facades\Auth;

class SettingComponent extends Component
{
    // ── General Settings Fields ─────────────────────────────
    public string $platformName  = '';
    public string $supportEmail  = '';
    public string $helpline      = '';

    // ── Security Fields ──────────────────────────────────────
    public string $adminPassword     = '';
    public string $adminPasswordConf = '';
    public bool   $smsOtpEnabled     = true;

    private array $generalKeyMap = [
        'platformName' => 'platform_name',
        'supportEmail' => 'support_email',
        'helpline'     => 'helpline',
    ];

    // ── Mount: load existing settings ───────────────────────
    public function mount(): void
    {
        $settings = AdminSetting::whereIn('key', [
            ...array_values($this->generalKeyMap),
            'sms_otp_enabled',
        ])->pluck('value', 'key');

        $this->platformName  = $settings->get('platform_name', 'KhaiKhai Food Delivery');
        $this->supportEmail  = $settings->get('support_email', 'support@khaikhai.com.bd');
        $this->helpline      = $settings->get('helpline', '09612-KHAI (5424)');
        $this->smsOtpEnabled = filter_var($settings->get('sms_otp_enabled', true), FILTER_VALIDATE_BOOLEAN);
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

    protected function securityRules(): array
    {
        return [
            'adminPassword'     => ['nullable', 'string', 'min:8', 'confirmed'],
            'smsOtpEnabled'     => ['boolean'],
        ];
    }

    protected array $generalMessages = [
        'platformName.required' => 'Platform name is required.',
        'supportEmail.required' => 'Support email is required.',
        'supportEmail.email'    => 'Please enter a valid email.',
        'helpline.required'     => 'Helpline number is required.',
    ];

    protected array $securityMessages = [
        'adminPassword.min'       => 'Password must be at least 8 characters.',
        'adminPassword.confirmed' => 'Password confirmation does not match.',
    ];

    // ── Save: General Settings ───────────────────────────────
    public function saveGeneral(): void
    {
        $validated = $this->validate($this->generalRules(), $this->generalMessages);

        foreach ($this->generalKeyMap as $property => $key) {
            AdminSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value'      => (string) $validated[$property],
                    'updated_by' => Auth::id(),
                ]
            );
        }

        session()->flash('success_general', 'General settings saved successfully.');
    }

    // ── Save: Security Settings ───────────────────────────────
    public function updateSecurity(): void
    {
        $this->validate($this->securityRules(), $this->securityMessages);

        if (filled($this->adminPassword)) {
            Auth::user()->update([
                'password' => $this->adminPassword,
            ]);
        }

        AdminSetting::updateOrCreate(
            ['key' => 'sms_otp_enabled'],
            [
                'value'      => $this->smsOtpEnabled ? '1' : '0',
                'updated_by' => Auth::id(),
            ]
        );

        $this->reset(['adminPassword', 'adminPasswordConf']);

        session()->flash('success_security', 'Security settings updated successfully.');
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.admin.setting-component')
            ->layout('layouts.admin', [
                'title'           => 'Settings | KhaiKhai',
                'breadcrumbTitle' => 'Platform Settings',
            ]);
    }
}