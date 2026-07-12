<div>
{{-- ═══════════════════════════════════════════════════════════
     VENDOR REGISTRATION WIZARD
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
        --step-done: #1D9E75;
        --step-active: #FF6B35;
        --step-pending: #CED4DA;
        --pink: #e91e8c;
        --pink-light: #FDF0F8;
    }

    * { box-sizing: border-box; }

    body {
        min-height: 100vh;
        font-family: 'Hind Siliguri', 'Segoe UI', sans-serif;
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
    .step-item.active .step-circle { background: var(--step-active); transform: scale(1.12); }
    .step-item.clickable .step-circle { cursor: pointer; }
    .step-item.clickable .step-circle:hover { transform: scale(1.08); }
    .step-label {
        font-size: .68rem; font-weight: 500;
        margin-top: 6px;
        color: var(--kk-dark);
        text-align: center;
        opacity: .6;
    }
    .step-item.active .step-label { opacity: 1; color: var(--kk-orange); }
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
        box-shadow: 0 0 0 3px rgba(255,107,53,.12);
        background-color: #fff;
        outline: none;
    }
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #DC3545;
        background-color: #FFF5F5;
    }
    .input-group-text {
        background: var(--pink-light);
        border: 1.5px solid var(--kk-border);
        border-right: none;
        color: var(--pink);
        border-radius: 10px 0 0 10px;
        font-size: .95rem;
    }
    .input-group .form-control { border-radius: 0 10px 10px 0; }
    .invalid-feedback { font-size: .78rem; color: #DC3545; margin-top: .3rem; }

    /* ── File upload ── */
    .upload-zone {
        border: 2px dashed var(--kk-border);
        border-radius: 14px;
        padding: 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all .2s;
        background: #FAFAFA;
        position: relative;
    }
    .upload-zone:hover { border-color: var(--pink); background: var(--pink-light); }
    .upload-zone input[type=file] {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        opacity: 0; cursor: pointer;
    }
    .upload-zone .upload-icon { font-size: 2rem; margin-bottom: .5rem; }
    .upload-zone .upload-text { font-size: .82rem; color: #6C757D; }
    .upload-zone .upload-hint { font-size: .72rem; color: #ADB5BD; margin-top: .25rem; }
    .preview-img {
        width: 100%; max-height: 140px;
        object-fit: cover; border-radius: 10px;
        border: 1.5px solid var(--kk-border);
    }
    .preview-logo {
        width: 80px; height: 80px;
        object-fit: cover; border-radius: 12px;
        border: 1.5px solid var(--kk-border);
    }

    /* ── Range slider ── */
    .range-row {
        display: flex; align-items: center; gap: 12px;
    }
    .range-row .form-range { flex: 1; accent-color: var(--pink); }
    .range-value {
        min-width: 52px; text-align: center;
        background: var(--pink-light);
        color: var(--pink);
        font-weight: 700; font-size: .85rem;
        padding: .3rem .6rem;
        border-radius: 8px;
        border: 1px solid rgba(255,107,53,.2);
    }

    /* ── Category pills ── */
    .category-grid {
        display: flex; flex-wrap: wrap; gap: .5rem;
    }
    .cat-pill {
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
    .cat-pill:hover { border-color: var(--pink); color: var(--pink); }
    .cat-pill.selected {
        background: var(--pink);
        border-color: var(--pink);
        color: #fff;
    }

    /* ── Toggle switch ── */
    .form-switch .form-check-input {
        width: 3em; height: 1.5em;
        cursor: pointer;
    }
    .form-switch .form-check-input:checked { background-color: var(--pink); border-color: var(--pink); }
    .form-switch .form-check-input:focus { box-shadow: 0 0 0 3px rgba(255,107,53,.2); }

    /* ── Summary box (step 4) ── */
    .summary-card {
        background: var(--kk-gray);
        border: 1px solid var(--kk-border);
        border-radius: 14px;
        padding: 1.2rem;
        margin-bottom: 1.5rem;
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
    .btn-kk-outline {
        background: #fff; color: var(--kk-dark);
        border: 1.5px solid var(--kk-border);
        border-radius: 12px; padding: .75rem 1.5rem;
        font-weight: 600; font-size: .95rem; transition: all .2s;
    }
    .btn-kk-outline:hover { border-color: var(--pink); color: var(--pink); }

    /* ── Footer ── */
    .wizard-footer {
        border-top: 1px solid var(--kk-border);
        padding: 1.2rem 2rem;
        display: flex; justify-content: space-between; align-items: center;
        background: #FAFAFA;
    }
    .progress-text { font-size: .78rem; color: #ADB5BD; }

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

    /* ── Responsive ── */
    @media (max-width: 576px) {
        .wizard-body { padding: 1.25rem; }
        .wizard-footer { padding: 1rem 1.25rem; }
        .step-label { display: none; }
        .btn-kk-primary, .btn-kk-outline { padding: .65rem 1.2rem; font-size: .85rem; }
    }
</style>

{{-- ── Page header ── --}}
<div class="wizard-header">
    <div class="container text-center">
        <div class="brand-emoji">🍽️</div>
        <h1>Restaurant Registration</h1>
        <p>Add your restaurant to KhaiKhai and reach millions of customers</p>
    </div>
</div>

<div class="container py-4" style="max-width: 720px;">

    {{-- ── Stepper ── --}}
    <div class="stepper mb-4">
        @php
            $stepLabels = ['Account', 'Restaurant', 'Media', 'Delivery'];
            $stepIcons  = ['👤', '🏠', '🖼️', '🚴'];
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
                <p class="step-subtitle">This information is needed to log in as a restaurant owner.</p>

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

                    {{-- Email --}}
                    <div class="col-sm-6">
                        <label class="form-label">Email Address <span class="req">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">✉️</span>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                wire:model.live.debounce.600ms="email"
                                placeholder="example@email.com">
                        </div>
                        @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    {{-- Phone --}}
                    <div class="col-sm-6">
                        <label class="form-label">Mobile Number <small class="text-muted">(optional)</small></label>
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
                        {{-- Strength bars --}}
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
                            <div>After creating your account, the KhaiKhai team will verify and approve your restaurant within 24 hours.</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ════════════════════════════════
                 STEP 2 — Restaurant Info
                 ════════════════════════════════ --}}
            @if($currentStep === 2)
                <h2 class="step-title">🏠 Restaurant Details</h2>
                <p class="step-subtitle">Customers will find your restaurant based on this information.</p>

                <div class="row g-3">
                    {{-- Restaurant name --}}
                    <div class="col-12">
                        <label class="form-label">Restaurant Name <span class="req">*</span></label>
                        <input type="text" class="form-control @error('restaurant_name') is-invalid @enderror"
                            wire:model.live.debounce.400ms="restaurant_name"
                            placeholder="e.g. Mother's Kitchen, Burger House">
                        @error('restaurant_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Category --}}
                    <div class="col-12">
                        <label class="form-label">Cuisine Type <span class="req">*</span></label>
                        <div class="category-grid">
                            @foreach($categories as $cat)
                                <span class="cat-pill {{ $category === $cat ? 'selected' : '' }}"
                                    wire:click="$set('category', '{{ $cat }}')">
                                    {{ $cat }}
                                </span>
                            @endforeach
                        </div>
                        @error('category') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    {{-- Phone --}}
                    <div class="col-sm-6">
                        <label class="form-label">Restaurant Phone <span class="req">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">📞</span>
                            <input type="tel" class="form-control @error('restaurant_phone') is-invalid @enderror"
                                wire:model.live.debounce.400ms="restaurant_phone"
                                placeholder="01XXXXXXXXX">
                        </div>
                        @error('restaurant_phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    {{-- City --}}
                    <div class="col-sm-6">
                        <label class="form-label">City <span class="req">*</span></label>
                        <select class="form-select @error('city') is-invalid @enderror"
                            wire:model.live="city">
                            <option value="">Select a city</option>
                            @foreach($cities as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Address --}}
                    <div class="col-12">
                        <label class="form-label">Full Address <span class="req">*</span></label>
                        <textarea class="form-control @error('address') is-invalid @enderror"
                            wire:model.live.debounce.400ms="address"
                            rows="2"
                            placeholder="House no, road, area...">{{ $address }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Postal --}}
                    <div class="col-sm-4">
                        <label class="form-label">Postal Code</label>
                        <input type="text" class="form-control @error('postal_code') is-invalid @enderror"
                            wire:model.live.debounce.400ms="postal_code"
                            placeholder="e.g. 1212">
                        @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Description --}}
                    <div class="col-12">
                        <label class="form-label">Short Description <small class="text-muted">(optional)</small></label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                            wire:model.live.debounce.500ms="description"
                            rows="3"
                            placeholder="Tell us a bit about your restaurant...">{{ $description }}</textarea>
                        <div class="d-flex justify-content-end">
                            <small class="text-muted">{{ strlen($description) }} / 500</small>
                        </div>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            @endif

            {{-- ════════════════════════════════
                 STEP 3 — Logo & Banner
                 ════════════════════════════════ --}}
            @if($currentStep === 3)
                <h2 class="step-title">🖼️ Logo & Banner</h2>
                <p class="step-subtitle">Great photos attract more customers. (You can skip this step)</p>

                <div class="info-alert warning">
                    <span class="info-icon">💡</span>
                    <div>You can also upload photos later from your Dashboard. This step is optional.</div>
                </div>

                <div class="row g-4">
                    {{-- Logo --}}
                    <div class="col-sm-6">
                        <label class="form-label">Restaurant Logo</label>
                        @if($logo)
                            <div class="text-center mb-2">
                                <img src="{{ $logo->temporaryUrl() }}" alt="Logo Preview" class="preview-logo">
                                <div style="font-size:.75rem;color:#6C757D;margin-top:4px;">{{ $logo->getClientOriginalName() }}</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger w-100 mb-2"
                                wire:click="$set('logo', null)">
                                🗑️ Remove
                            </button>
                        @else
                            <div class="upload-zone">
                                <input type="file" wire:model="logo" accept="image/*">
                                <div class="upload-icon">🏷️</div>
                                <div class="upload-text">Upload Logo</div>
                                <div class="upload-hint">JPG, PNG, WEBP · Max 2 MB</div>
                                <div class="upload-hint">Recommended: 500×500 px</div>
                            </div>
                        @endif
                        @error('logo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    {{-- Banner --}}
                    <div class="col-sm-6">
                        <label class="form-label">Cover Banner</label>
                        @if($banner)
                            <img src="{{ $banner->temporaryUrl() }}" alt="Banner Preview" class="preview-img mb-2">
                            <div style="font-size:.75rem;color:#6C757D;margin-bottom:8px;">{{ $banner->getClientOriginalName() }}</div>
                            <button type="button" class="btn btn-sm btn-outline-danger w-100"
                                wire:click="$set('banner', null)">
                                🗑️ Remove
                            </button>
                        @else
                            <div class="upload-zone">
                                <input type="file" wire:model="banner" accept="image/*">
                                <div class="upload-icon">🖼️</div>
                                <div class="upload-text">Upload Banner</div>
                                <div class="upload-hint">JPG, PNG, WEBP · Max 4 MB</div>
                                <div class="upload-hint">Recommended: 1200×400 px</div>
                            </div>
                        @endif
                        @error('banner') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Upload progress --}}
                <div wire:loading wire:target="logo,banner" class="mt-3">
                    <div class="progress" style="height:6px; border-radius:99px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                            style="width:100%; background:var(--pink);"></div>
                    </div>
                    <div style="font-size:.78rem; color:#6C757D; margin-top:4px; text-align:center;">
                        Uploading...
                    </div>
                </div>
            @endif

            {{-- ════════════════════════════════
                 STEP 4 — Delivery Settings + Summary
                 ════════════════════════════════ --}}
            @if($currentStep === 4)
                <h2 class="step-title">🚴 Delivery Settings</h2>
                <p class="step-subtitle">Customers will see this information before placing an order.</p>

                <div class="row g-3">

                    {{-- Delivery fee --}}
                    <div class="col-sm-6">
                        <label class="form-label">Delivery Fee (BDT) <span class="req">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" class="form-control @error('delivery_fee') is-invalid @enderror"
                                wire:model.live="delivery_fee"
                                min="0" max="1000" step="1">
                        </div>
                        @error('delivery_fee') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    {{-- Min order --}}
                    <div class="col-sm-6">
                        <label class="form-label">Minimum Order (BDT)</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" class="form-control @error('min_order_amount') is-invalid @enderror"
                                wire:model.live="min_order_amount"
                                min="0" step="10">
                        </div>
                        @error('min_order_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    {{-- Delivery time range --}}
                    <div class="col-12">
                        <label class="form-label">Delivery Time Range <span class="req">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label style="font-size:.75rem; color:#6C757D;">Minimum (minutes)</label>
                                <div class="range-row">
                                    <input type="range" class="form-range"
                                        wire:model.live="avg_delivery_min"
                                        min="5" max="120" step="5">
                                    <span class="range-value">{{ $avg_delivery_min }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label style="font-size:.75rem; color:#6C757D;">Maximum (minutes)</label>
                                <div class="range-row">
                                    <input type="range" class="form-range"
                                        wire:model.live="avg_delivery_max"
                                        min="5" max="180" step="5">
                                    <span class="range-value">{{ $avg_delivery_max }}</span>
                                </div>
                            </div>
                        </div>
                        @error('avg_delivery_max') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div style="font-size:.75rem; color:#6C757D; margin-top:4px;">
                            Customers will see: <strong>{{ $avg_delivery_min }}–{{ $avg_delivery_max }} minutes</strong>
                        </div>
                    </div>

                    {{-- Prep time --}}
                    <div class="col-sm-6">
                        <label class="form-label">Preparation Time (minutes) <span class="req">*</span></label>
                        <div class="range-row">
                            <input type="range" class="form-range"
                                wire:model.live="prep_time_min"
                                min="5" max="120" step="5">
                            <span class="range-value">{{ $prep_time_min }}</span>
                        </div>
                    </div>

                    {{-- Auto accept --}}
                    <div class="col-sm-6">
                        <label class="form-label">Auto-Accept Orders</label>
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox"
                                wire:model.live="auto_accept" id="autoAccept">
                            <label class="form-check-label" for="autoAccept" style="font-size:.85rem;">
                                @if($auto_accept)
                                    ✅ New orders will be accepted automatically
                                @else
                                    ⏳ Each order must be accepted manually
                                @endif
                            </label>
                        </div>
                    </div>

                    {{-- Summary --}}
                    <div class="col-12 mt-2">
                        <div style="font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#6C757D; margin-bottom:.8rem;">
                            📋 Registration Summary
                        </div>

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
                            <div class="s-title">🏠 Restaurant</div>
                            <div class="summary-row">
                                <span class="sr-label">Name</span>
                                <span class="sr-value">{{ $restaurant_name }}</span>
                            </div>
                            <div class="summary-row">
                                <span class="sr-label">Category</span>
                                <span class="sr-value">{{ $category }}</span>
                            </div>
                            <div class="summary-row">
                                <span class="sr-label">City</span>
                                <span class="sr-value">{{ $city }}</span>
                            </div>
                            <div class="summary-row">
                                <span class="sr-label">Logo</span>
                                <span class="sr-value">{{ $logo ? '✅ Uploaded' : '—' }}</span>
                            </div>
                            <div class="summary-row">
                                <span class="sr-label">Banner</span>
                                <span class="sr-value">{{ $banner ? '✅ Uploaded' : '—' }}</span>
                            </div>
                        </div>

                        <div class="summary-card">
                            <div class="s-title">🚴 Delivery</div>
                            <div class="summary-row">
                                <span class="sr-label">Fee</span>
                                <span class="sr-value">৳{{ $delivery_fee }}</span>
                            </div>
                            <div class="summary-row">
                                <span class="sr-label">Time</span>
                                <span class="sr-value">{{ $avg_delivery_min }}–{{ $avg_delivery_max }} minutes</span>
                            </div>
                            <div class="summary-row">
                                <span class="sr-label">Prep Time</span>
                                <span class="sr-value">{{ $prep_time_min }} minutes</span>
                            </div>
                            <div class="summary-row">
                                <span class="sr-label">Auto-Accept</span>
                                <span class="sr-value">{{ $auto_accept ? 'Yes' : 'No' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="info-alert success">
                            <span class="info-icon">✅</span>
                            <div>After submitting, the KhaiKhai team will verify your information. You'll receive an SMS once verification is complete.</div>
                        </div>
                    </div>
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
                        <span wire:loading.remove wire:target="submit">✅ Complete Registration</span>
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
        By registering, you agree to KhaiKhai's
        <a href="#" style="color:var(--pink);">Terms & Conditions</a> and
        <a href="#" style="color:var(--pink);">Privacy Policy</a>.
    </p>

</div>{{-- container --}}

</div>{{-- main div --}}