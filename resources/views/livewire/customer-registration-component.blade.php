<div>
{{-- ═══════════════════════════════════════════════════════════
     CUSTOMER REGISTRATION (Single Step)
     Bootstrap 5.3 + Livewire v4
     KhaiKhai Food Delivery Platform
     ═══════════════════════════════════════════════════════════ --}}

<style>
    :root {
        --kk-orange: #FF6B35;
        --kk-orange-dark: #E55A25;
        --kk-orange-light: #FFF0EB;
        --kk-green: #1D9E75;
        --kk-green-light: #E8F7F2;
        --kk-dark: #1A1A2E;
        --kk-gray: #F8F9FA;
        --kk-border: #E8E8F0;
        --pink: #e91e8c;
    }

    * { box-sizing: border-box; }

    body {
        min-height: 100vh;
        font-family: 'Segoe UI', sans-serif;
    }

    /* ── Page header ── */
    .wizard-header {
        background: linear-gradient(135deg, #3d0a6e 0%, #7c1e8c 50%, var(--pink) 100%);
        padding: 2rem 0 4rem;
        position: relative;
        overflow: hidden;
    }
    .wizard-header::after {
        content: '';
        position: absolute;
        bottom: -1px; left: 0; right: 0;
        height: 40px;
        background: #FFF8F5;
        clip-path: ellipse(55% 100% at 50% 100%);
    }
    .wizard-header .brand-emoji { font-size: 2.8rem; }
    .wizard-header h1 { color: #fff; font-weight: 700; font-size: 1.6rem; }
    .wizard-header p  { color: rgba(255,255,255,.82); font-size: .9rem; }

    /* ── Card ── */
    .wizard-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 8px 40px rgba(26,26,46,.08);
        border: 1px solid var(--kk-border);
        overflow: hidden;
    }
    .wizard-body { padding: 2rem; }
    .step-title {
        font-size: 1.2rem; font-weight: 700;
        color: var(--kk-dark); margin-bottom: .25rem;
    }
    .step-subtitle {
        font-size: .85rem; color: #6C757D;
        margin-bottom: 1.5rem;
        padding-bottom: 1.2rem;
        border-bottom: 1px dashed var(--kk-border);
    }

    /* ── Form controls ── */
    .form-label {
        font-size: .82rem; font-weight: 600;
        color: var(--kk-dark); margin-bottom: .35rem;
    }
    .form-label .req { color: var(--v); margin-left: 2px; }
    .form-control {
        border: 1.5px solid var(--kk-border);
        border-radius: 10px;
        padding: .65rem 1rem;
        font-size: .9rem;
        transition: border-color .2s, box-shadow .2s;
        background-color: #FAFAFA;
    }
    .form-control:focus {
        border-color: var(--pink);
        box-shadow: 0 0 0 3px rgba(233,30,140,.12);
        background-color: #fff;
        outline: none;
    }
    .form-control.is-invalid { border-color: #DC3545; background-color: #FFF5F5; }
    .input-group-text {
        background: #FCE9F5;
        border: 1.5px solid var(--kk-border);
        border-right: none;
        color: var(--pink);
        border-radius: 10px 0 0 10px;
        font-size: .95rem;
    }
    .input-group .form-control { border-radius: 0 10px 10px 0; }
    .invalid-feedback { font-size: .78rem; color: #DC3545; margin-top: .3rem; }

    /* ── Info alert ── */
    .info-alert {
        background: #E8F4FD; border: 1px solid #B8DFFE;
        border-radius: 12px; padding: 1rem 1.2rem;
        font-size: .82rem; color: #0A6EBD;
        display: flex; gap: .7rem; align-items: flex-start;
        margin-bottom: 1.2rem;
    }
    .info-alert .info-icon { font-size: 1.1rem; flex-shrink: 0; }

    /* ── Buttons ── */
    .btn-kk-primary {
        background: linear-gradient(135deg, #7c1e8c 0%, var(--pink) 100%);
        color: #fff; border: none; border-radius: 12px;
        padding: .75rem 1.8rem; font-weight: 700; font-size: .95rem;
        transition: all .2s;
        box-shadow: 0 4px 15px rgba(233,30,140,.3);
        width: 100%;
    }
    .btn-kk-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(233,30,140,.4);
        color: #fff;
    }
    .btn-kk-primary:disabled { opacity: .65; transform: none; }

    /* ── Password strength ── */
    .pw-strength { display: flex; gap: 4px; margin-top: 6px; }
    .pw-bar {
        height: 4px; flex: 1; border-radius: 99px;
        background: var(--kk-border); transition: background .3s;
    }
    .pw-bar.weak   { background: #DC3545; }
    .pw-bar.fair   { background: #FFC107; }
    .pw-bar.strong { background: var(--kk-green); }
    .pw-label { font-size: .72rem; margin-top: 4px; color: #6C757D; }

    @media (max-width: 576px) {
        .wizard-body { padding: 1.25rem; }
        .btn-kk-primary { padding: .65rem 1.2rem; font-size: .85rem; }
    }
</style>

{{-- ── Page header ── --}}
<div class="wizard-header">
    <div class="container text-center">
        <div class="brand-emoji">🍽️</div>
        <h1>Customer Registration</h1>
        <p>Create your KhaiKhai account and start ordering food</p>
    </div>
</div>

<div class="container py-4" style="max-width: 560px;">

    <div class="wizard-card">
        <div class="wizard-body">

            <h2 class="step-title">👤 Create Your Account</h2>
            <p class="step-subtitle">Fill in the details below to register quickly and start ordering.</p>

            <div class="row g-3">
                {{-- Name --}}
                <div class="col-12">
                    <label class="form-label">Full Name <span class="req">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">👤</span>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            wire:model.live.debounce.400ms="name"
                            placeholder="Enter your full name">
                    </div>
                    @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                {{-- Email (required) --}}
                <div class="col-12">
                    <label class="form-label">Email <span class="req">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">✉️</span>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                            wire:model.live.debounce.600ms="email"
                            placeholder="example@email.com">
                    </div>
                    @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                {{-- Phone (optional) --}}
                <div class="col-12">
                    <label class="form-label">Mobile Number <small class="text-muted">(Optional)</small></label>
                    <div class="input-group">
                        <span class="input-group-text">📱</span>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                            wire:model.live.debounce.600ms="phone"
                            placeholder="01XXXXXXXXX" maxlength="11">
                    </div>
                    @error('phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                {{-- Password --}}
                <div class="col-sm-6">
                    <label class="form-label">Password <span class="req">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">🔒</span>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                            wire:model.live="password"
                            placeholder="At least 8 characters" id="pwInput">
                    </div>
                    @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    @if(strlen($password) > 0)
                        @php
                            $len = strlen($password);
                            $hasNum = preg_match('/[0-9]/', $password);
                            $hasSym = preg_match('/[^a-zA-Z0-9]/', $password);
                            $strength = ($len >= 8 ? 1 : 0) + ($hasNum ? 1 : 0) + ($hasSym ? 1 : 0);
                            $strengthLabel = ['Weak','Fair','Strong'][$strength - 1] ?? 'Very Weak';
                            $strengthClass = ['','weak','fair','strong'][$strength] ?? '';
                        @endphp
                        <div class="pw-strength">
                            <div class="pw-bar {{ $strength >= 1 ? $strengthClass : '' }}"></div>
                            <div class="pw-bar {{ $strength >= 2 ? $strengthClass : '' }}"></div>
                            <div class="pw-bar {{ $strength >= 3 ? $strengthClass : '' }}"></div>
                        </div>
                        <div class="pw-label">Password strength: {{ $strengthLabel }}</div>
                    @endif
                </div>

                {{-- Confirm Password --}}
                <div class="col-sm-6">
                    <label class="form-label">Confirm Password <span class="req">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">🔑</span>
                        <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                            wire:model.live="password_confirmation"
                            placeholder="Re-enter your password">
                    </div>
                    @if(strlen($password_confirmation) > 0)
                        @if($password === $password_confirmation)
                            <div class="text-success" style="font-size:.75rem; margin-top:4px;">✓ Passwords match</div>
                        @else
                            <div class="text-danger" style="font-size:.75rem; margin-top:4px;">✗ Passwords do not match</div>
                        @endif
                    @endif
                    @error('password_confirmation') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                {{-- Info --}}
                <div class="col-12">
                    <div class="info-alert">
                        <span class="info-icon">ℹ️</span>
                        <div>Once registration is complete, you can start ordering food immediately.</div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="col-12 mt-2">
                    <button type="button" class="btn-kk-primary"
                        wire:click="submit"
                        wire:loading.attr="disabled"
                        wire:target="submit">
                        <span wire:loading.remove wire:target="submit">✅ Complete Registration</span>
                        <span wire:loading wire:target="submit">
                            <span class="spinner-border spinner-border-sm me-1"></span> Submitting...
                        </span>
                    </button>
                </div>
            </div>

        </div>{{-- wizard-body --}}
    </div>{{-- wizard-card --}}

    {{-- Terms note --}}
    <p class="text-center mt-3" style="font-size:.78rem; color:#ADB5BD;">
        By registering, you agree to KhaiKhai's
        <a href="#" style="color:var(--pink);">Terms & Conditions</a> and
        <a href="#" style="color:var(--pink);">Privacy Policy</a>.
    </p>

</div>{{-- container --}}

</div>{{-- main div --}}