<div>
{{-- ═══════════════════════════════════════════════════════════
     RIDER REGISTRATION WIZARD
     Bootstrap 5.3 + Livewire v3
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
        --step-done: #1D9E75;
        --step-active: #e91e8c;
        --step-pending: #CED4DA;
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

    /* ── Stepper ── */
    .stepper {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        padding: 0 1rem;
    }
    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        flex: 1;
        max-width: 130px;
    }
    .step-item:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 20px; left: calc(50% + 20px);
        width: calc(100% - 40px);
        height: 2px;
        background: var(--step-pending);
        transition: background .4s;
    }
    .step-item.done:not(:last-child)::after,
    .step-item.active:not(:last-child)::after {
        background: var(--step-done);
    }
    .step-circle {
        width: 40px; height: 40px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .9rem;
        background: var(--step-pending);
        color: #fff;
        border: 3px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,.12);
        transition: background .3s, transform .2s;
        cursor: default;
        z-index: 2; position: relative;
    }
    .step-item.done   .step-circle { background: var(--step-done); }
    .step-item.active .step-circle { background: var(--pink); transform: scale(1.12); }
    .step-item.clickable .step-circle { cursor: pointer; }
    .step-item.clickable .step-circle:hover { transform: scale(1.08); }
    .step-label {
        font-size: .68rem; font-weight: 500;
        margin-top: 6px;
        color: #fff;
        text-align: center;
        opacity: .6;
    }
    .step-item.active .step-label { opacity: 1; color: var(--pink); }
    .step-item.done   .step-label { opacity: .9; }

    /* ── Wizard card ── */
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
    .form-label .req { color: var(--pink); margin-left: 2px; }
    .form-control, .form-select {
        border: 1.5px solid var(--kk-border);
        border-radius: 10px;
        padding: .65rem 1rem;
        font-size: .9rem;
        transition: border-color .2s, box-shadow .2s;
        background-color: #FAFAFA;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--pink);
        box-shadow: 0 0 0 3px rgba(233,30,140,.12);
        background-color: #fff;
        outline: none;
    }
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #DC3545;
        background-color: #FFF5F5;
    }
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

    /* ── Vehicle type cards ── */
    .vehicle-grid {
        display: flex; flex-wrap: wrap; gap: .75rem;
    }
    .vehicle-card {
        flex: 1; min-width: 90px; max-width: 130px;
        border: 2px solid var(--kk-border);
        border-radius: 14px;
        padding: 1rem .75rem;
        text-align: center;
        cursor: pointer;
        transition: all .18s;
        background: #fff;
        user-select: none;
    }
    .vehicle-card:hover {
        border-color: var(--pink);
        background: #FCE9F5;
    }
    .vehicle-card.selected {
        border-color: var(--pink);
        background: var(--pink);
        color: #fff;
    }
    .vehicle-card .v-icon { font-size: 1.8rem; display: block; margin-bottom: .35rem; }
    .vehicle-card .v-label { font-size: .75rem; font-weight: 600; }
    .vehicle-card.selected .v-label { color: #fff; }

    /* ── Zone pills ── */
    .zone-grid {
        display: flex; flex-wrap: wrap; gap: .5rem;
    }
    .zone-pill {
        padding: .4rem .9rem;
        border-radius: 99px;
        border: 1.5px solid var(--kk-border);
        font-size: .8rem; font-weight: 500;
        cursor: pointer;
        transition: all .15s;
        user-select: none;
        background: #fff;
        color: #495057;
    }
    .zone-pill:hover { border-color: var(--pink); color: var(--pink); }
    .zone-pill.selected {
        background: var(--pink);
        border-color: var(--pink);
        color: #fff;
    }

    /* ── Info alert ── */
    .info-alert {
        background: #E8F4FD; border: 1px solid #B8DFFE;
        border-radius: 12px; padding: 1rem 1.2rem;
        font-size: .82rem; color: #0A6EBD;
        display: flex; gap: .7rem; align-items: flex-start;
        margin-bottom: 1.2rem;
    }
    .info-alert .info-icon { font-size: 1.1rem; flex-shrink: 0; }
    .info-alert.success {
        background: var(--kk-green-light); border-color: #A7E3CF;
        color: var(--kk-green);
    }
    .info-alert.warning {
        background: #FFF8E1; border-color: #FFE082; color: #856404;
    }

    /* ── Summary box ── */
    .summary-card {
        background: var(--kk-gray);
        border: 1px solid var(--kk-border);
        border-radius: 14px;
        padding: 1.2rem;
        margin-bottom: 1rem;
    }
    .summary-card .s-title {
        font-size: .72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .5px;
        color: #6C757D; margin-bottom: .8rem;
    }
    .summary-row {
        display: flex; justify-content: space-between;
        font-size: .83rem; padding: .3rem 0;
        border-bottom: 1px solid var(--kk-border);
    }
    .summary-row:last-child { border-bottom: none; }
    .summary-row .sr-label { color: #6C757D; }
    .summary-row .sr-value { font-weight: 600; color: var(--kk-dark); }

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

    /* ── Buttons ── */
    .btn-kk-primary {
        background: linear-gradient(135deg, #7c1e8c 0%, var(--pink) 100%);
        color: #fff; border: none; border-radius: 12px;
        padding: .75rem 1.8rem; font-weight: 700; font-size: .95rem;
        transition: all .2s;
        box-shadow: 0 4px 15px rgba(233,30,140,.3);
    }
    .btn-kk-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(233,30,140,.4);
        color: #fff;
    }
    .btn-kk-primary:disabled { opacity: .65; transform: none; }
    .btn-kk-outline {
        background: #fff; color: var(--kk-dark);
        border: 1.5px solid var(--kk-border);
        border-radius: 12px; padding: .75rem 1.5rem;
        font-weight: 600; font-size: .95rem; transition: all .2s;
    }
    .btn-kk-outline:hover { border-color: var(--pink); color: var(--pink); }

    /* ── Wizard footer ── */
    .wizard-footer {
        border-top: 1px solid var(--kk-border);
        padding: 1.2rem 2rem;
        display: flex; justify-content: space-between; align-items: center;
        background: #FAFAFA;
    }
    .progress-text { font-size: .78rem; color: #ADB5BD; }

    /* ── Responsive ── */
    @media (max-width: 576px) {
        .wizard-body { padding: 1.25rem; }
        .wizard-footer { padding: 1rem 1.25rem; }
        .step-label { display: none; }
        .btn-kk-primary, .btn-kk-outline { padding: .65rem 1.2rem; font-size: .85rem; }
        .vehicle-card { min-width: 75px; }
    }
</style>

{{-- ── Page header ── --}}
<div class="wizard-header">
    <div class="container text-center">
        <div class="brand-emoji">🏍️</div>
        <h1>Rider Registration</h1>
        <p>Join KhaiKhai as a rider and start earning</p>
    </div>
</div>

<div class="container py-4" style="max-width: 720px;">

    {{-- ── Stepper ── --}}
    <div class="stepper mb-4">
        @php
            $stepLabels = ['Account', 'Vehicle', 'Zone', 'Confirm'];
            $stepIcons  = ['👤', '🏍️', '📍', '✅'];
        @endphp
        @for ($i = 1; $i <= $totalSteps; $i++)
            <div class="step-item
                {{ $i < $currentStep ? 'done' : '' }}
                {{ $i === $currentStep ? 'active' : '' }}
                {{ $i < $currentStep ? 'clickable' : '' }}"
                @if($i < $currentStep) wire:click="goToStep({{ $i }})" @endif>
                <div class="step-circle">
                    @if($i < $currentStep)
                        ✓
                    @else
                        {{ $stepIcons[$i - 1] }}
                    @endif
                </div>
                <span class="step-label">{{ $stepLabels[$i - 1] }}</span>
            </div>
        @endfor
    </div>

    {{-- ── Wizard card ── --}}
    <div class="wizard-card">
        <div class="wizard-body">

            {{-- ════════════════════════════════
                 STEP 1 — Account Info
                 ════════════════════════════════ --}}
            @if($currentStep === 1)
                <h2 class="step-title">👤 Create Your Account</h2>
                <p class="step-subtitle">This information will be used to log in as a rider.</p>

                <div class="row g-3">
                    {{-- Name --}}
                    <div class="col-12">
                        <label class="form-label">Full Name <span class="req">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">👤</span>
                            <input type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                wire:model.live.debounce.400ms="name"
                                placeholder="Enter your full name">
                        </div>
                        @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    {{-- Email --}}
                    <div class="col-sm-6">
                        <label class="form-label">Email Address <span class="req">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">✉️</span>
                            <input type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                wire:model.live.debounce.600ms="email"
                                placeholder="example@email.com">
                        </div>
                        @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    {{-- Phone --}}
                    <div class="col-sm-6">
                        <label class="form-label">Mobile Number <small class="text-muted">(Optional)</small></label>
                        <div class="input-group">
                            <span class="input-group-text">📱</span>
                            <input type="tel"
                                class="form-control @error('phone') is-invalid @enderror"
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
                            <input type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                wire:model.live="password"
                                placeholder="At least 8 characters">
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
                            <input type="password"
                                class="form-control @error('password_confirmation') is-invalid @enderror"
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

                    <div class="col-12">
                        <div class="info-alert">
                            <span class="info-icon">ℹ️</span>
                            <div>After submitting your application, the KhaiKhai team will review and approve your information within 48 hours.</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ════════════════════════════════
                 STEP 2 — Vehicle & Documents
                 ════════════════════════════════ --}}
            @if($currentStep === 2)
                <h2 class="step-title">🏍️ Vehicle & Documents</h2>
                <p class="step-subtitle">Provide your vehicle and identification details.</p>

                <div class="row g-3">
                    {{-- Vehicle type --}}
                    <div class="col-12">
                        <label class="form-label">Vehicle Type <span class="req">*</span></label>
                        <div class="vehicle-grid">
                            @foreach($vehicleTypes as $type => $icon)
                                <div class="vehicle-card {{ $vehicle_type === $type ? 'selected' : '' }}"
                                    wire:click="$set('vehicle_type', '{{ $type }}')">
                                    <span class="v-icon">{{ $icon }}</span>
                                    <span class="v-label">{{ $type }}</span>
                                </div>
                            @endforeach
                        </div>
                        @error('vehicle_type') <div class="invalid-feedback d-block mt-2">{{ $message }}</div> @enderror
                    </div>

                    {{-- Vehicle plate --}}
                    <div class="col-sm-6">
                        <label class="form-label">Vehicle Plate Number <small class="text-muted">(Optional)</small></label>
                        <div class="input-group">
                            <span class="input-group-text">🔢</span>
                            <input type="text"
                                class="form-control @error('vehicle_plate') is-invalid @enderror"
                                wire:model.live.debounce.500ms="vehicle_plate"
                                placeholder="e.g. Dhaka-Metro-Ga-1234">
                        </div>
                        @error('vehicle_plate') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    {{-- License --}}
                    <div class="col-sm-6">
                        <label class="form-label">Driving License <small class="text-muted">(Optional)</small></label>
                        <div class="input-group">
                            <span class="input-group-text">📄</span>
                            <input type="text"
                                class="form-control @error('license_number') is-invalid @enderror"
                                wire:model.live.debounce.500ms="license_number"
                                placeholder="License number">
                        </div>
                        @error('license_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    {{-- NID --}}
                    <div class="col-sm-6">
                        <label class="form-label">National ID (NID) <small class="text-muted">(Optional)</small></label>
                        <div class="input-group">
                            <span class="input-group-text">🪪</span>
                            <input type="text"
                                class="form-control @error('nid_number') is-invalid @enderror"
                                wire:model.live.debounce.500ms="nid_number"
                                placeholder="NID number" maxlength="20">
                        </div>
                        @error('nid_number') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <div class="info-alert warning">
                            <span class="info-icon">💡</span>
                            <div>If you don't have your driving license or NID now, you can update them later from the Dashboard. However, they are required for final approval.</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ════════════════════════════════
                 STEP 3 — Zone Selection
                 ════════════════════════════════ --}}
            @if($currentStep === 3)
                <h2 class="step-title">📍 Delivery Zone</h2>
                <p class="step-subtitle">Which area would you like to deliver in?</p>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Select Zone <span class="req">*</span></label>
                        <div class="zone-grid">
                            @foreach($zones as $z)
                                <span class="zone-pill {{ $zone === $z ? 'selected' : '' }}"
                                    wire:click="$set('zone', '{{ $z }}')">
                                    📍 {{ $z }}
                                </span>
                            @endforeach
                        </div>
                        @error('zone') <div class="invalid-feedback d-block mt-2">{{ $message }}</div> @enderror
                    </div>

                    @if($zone)
                        <div class="col-12">
                            <div class="info-alert success">
                                <span class="info-icon">✅</span>
                                <div>You have selected the <strong>{{ $zone }}</strong> zone. Active orders in this zone will be sent to you.</div>
                            </div>
                        </div>
                    @endif

                    <div class="col-12">
                        <div class="info-alert">
                            <span class="info-icon">ℹ️</span>
                            <div>You can change your zone from the Dashboard after approval.</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ════════════════════════════════
                 STEP 4 — Review & Submit
                 ════════════════════════════════ --}}
            @if($currentStep === 4)
                <h2 class="step-title">✅ Review Your Information</h2>
                <p class="step-subtitle">Please review your details before submitting.</p>

                <div class="summary-card">
                    <div class="s-title">👤 Account</div>
                    <div class="summary-row">
                        <span class="sr-label">Name</span>
                        <span class="sr-value">{{ $name }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="sr-label">Email</span>
                        <span class="sr-value">{{ $email }}</span>
                    </div>
                    @if($phone)
                    <div class="summary-row">
                        <span class="sr-label">Phone</span>
                        <span class="sr-value">{{ $phone }}</span>
                    </div>
                    @endif
                </div>

                <div class="summary-card">
                    <div class="s-title">🏍️ Vehicle</div>
                    <div class="summary-row">
                        <span class="sr-label">Type</span>
                        <span class="sr-value">
                            {{ $vehicleTypes[$vehicle_type] ?? '' }} {{ $vehicle_type }}
                        </span>
                    </div>
                    <div class="summary-row">
                        <span class="sr-label">Plate Number</span>
                        <span class="sr-value">{{ $vehicle_plate ?: '—' }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="sr-label">License</span>
                        <span class="sr-value">{{ $license_number ?: '—' }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="sr-label">NID</span>
                        <span class="sr-value">{{ $nid_number ?: '—' }}</span>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="s-title">📍 Zone</div>
                    <div class="summary-row">
                        <span class="sr-label">Delivery Zone</span>
                        <span class="sr-value">📍 {{ $zone }}</span>
                    </div>
                </div>

                <div class="info-alert success">
                    <span class="info-icon">✅</span>
                    <div>After submission, the KhaiKhai team will review your information. Once approved, you'll receive an SMS and can start delivering.</div>
                </div>
            @endif

        </div>{{-- wizard-body --}}

        {{-- ── Footer buttons ── --}}
        <div class="wizard-footer">
            <div>
                @if($currentStep > 1)
                    <button type="button" class="btn-kk-outline"
                        wire:click="prevStep"
                        wire:loading.attr="disabled">
                        ← Back
                    </button>
                @else
                    <span class="progress-text">Step {{ $currentStep }} / {{ $totalSteps }}</span>
                @endif
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="progress-text">Step {{ $currentStep }} / {{ $totalSteps }}</span>

                @if($currentStep < $totalSteps)
                    <button type="button" class="btn-kk-primary"
                        wire:click="nextStep"
                        wire:loading.attr="disabled"
                        wire:target="nextStep">
                        <span wire:loading.remove wire:target="nextStep">Next →</span>
                        <span wire:loading wire:target="nextStep">
                            <span class="spinner-border spinner-border-sm me-1"></span> Validating...
                        </span>
                    </button>
                @else
                    <button type="button" class="btn-kk-primary"
                        wire:click="submit"
                        wire:loading.attr="disabled"
                        wire:target="submit">
                        <span wire:loading.remove wire:target="submit">✅ Complete Application</span>
                        <span wire:loading wire:target="submit">
                            <span class="spinner-border spinner-border-sm me-1"></span> Submitting...
                        </span>
                    </button>
                @endif
            </div>
        </div>

    </div>{{-- wizard-card --}}

    {{-- Terms note --}}
    <p class="text-center mt-3" style="font-size:.78rem; color:#ADB5BD;">
        By applying, you agree to KhaiKhai's
        <a href="#" style="color:var(--pink);">Terms & Conditions</a> and
        <a href="#" style="color:var(--pink);">Privacy Policy</a>.
    </p>

</div>{{-- container --}}

</div>{{-- main div --}}