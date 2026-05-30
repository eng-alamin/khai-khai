{{-- resources/views/livewire/login.blade.php --}}
<div>
<style>
    :root {
        --kk-orange: #FF6B35;
        --kk-orange-dark: #E55A25;
        --kk-orange-light: #FFF0EB;
        --kk-green: #1D9E75;
        --kk-dark: #1A1A2E;
        --kk-border: #E8E8F0;
    }
    body {
        background: linear-gradient(135deg, #FFF8F5 0%, #F0FDF9 100%);
        min-height: 100vh;
        font-family: 'Hind Siliguri', 'Segoe UI', sans-serif;
    }

    /* ── Card ── */
    .login-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 8px 40px rgba(26,26,46,.09);
        border: 1px solid var(--kk-border);
        overflow: hidden;
    }
    .login-header {
        background: linear-gradient(135deg, var(--kk-orange) 0%, #FF8C42 100%);
        padding: 2rem;
        text-align: center;
    }
    .login-header .brand-emoji { font-size: 2.5rem; }
    .login-header h1 { color: #fff; font-weight: 700; font-size: 1.4rem; margin: .4rem 0 .25rem; }
    .login-header p  { color: rgba(255,255,255,.85); font-size: .85rem; margin: 0; }
    .login-body { padding: 2rem; }

    /* ── Form controls ── */
    .form-label {
        font-size: .82rem; font-weight: 600;
        color: var(--kk-dark); margin-bottom: .35rem;
    }
    .form-control {
        border: 1.5px solid var(--kk-border);
        border-radius: 10px;
        padding: .65rem 1rem;
        font-size: .9rem;
        background: #FAFAFA;
        transition: border-color .2s, box-shadow .2s;
    }
    .form-control:focus {
        border-color: var(--kk-orange);
        box-shadow: 0 0 0 3px rgba(255,107,53,.12);
        background: #fff;
        outline: none;
    }
    .form-control.is-invalid { border-color: #DC3545; background: #FFF5F5; }
    .input-group-text {
        background: var(--kk-orange-light);
        border: 1.5px solid var(--kk-border);
        border-right: none;
        color: var(--kk-orange);
        border-radius: 10px 0 0 10px;
        font-size: 1rem;
    }
    .input-group .form-control { border-radius: 0 10px 10px 0; }
    .invalid-feedback { font-size: .78rem; }

    /* ── Password toggle ── */
    .pw-wrap { position: relative; }
    .pw-toggle {
        position: absolute; right: 12px; top: 50%;
        transform: translateY(-50%);
        background: none; border: none;
        color: #ADB5BD; cursor: pointer; font-size: 1rem;
        padding: 0; line-height: 1;
    }
    .pw-toggle:hover { color: var(--kk-orange); }

    /* ── Error alert ── */
    .error-alert {
        background: #FFF0F0; border: 1px solid #FFCDD2;
        border-radius: 10px; padding: .85rem 1rem;
        font-size: .85rem; color: #C62828;
        display: flex; gap: 8px; align-items: flex-start;
        margin-bottom: 1.2rem;
    }

    /* ── Primary button ── */
    .btn-kk {
        background: linear-gradient(135deg, var(--kk-orange) 0%, #FF8C42 100%);
        color: #fff; border: none; border-radius: 12px;
        padding: .8rem; font-weight: 700; font-size: 1rem;
        width: 100%; transition: all .2s;
        box-shadow: 0 4px 15px rgba(255,107,53,.3);
    }
    .btn-kk:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(255,107,53,.4);
        color: #fff;
    }
    .btn-kk:disabled { opacity: .65; transform: none; }

    /* ── Divider ── */
    .divider {
        display: flex; align-items: center; gap: 12px;
        margin: 1.2rem 0; color: #ADB5BD; font-size: .8rem;
    }
    .divider::before, .divider::after {
        content: ''; flex: 1; height: 1px;
        background: var(--kk-border);
    }

    /* ── Register link box ── */
    .register-box {
        background: var(--kk-orange-light);
        border: 1px solid rgba(255,107,53,.2);
        border-radius: 12px; padding: 1rem;
        text-align: center; font-size: .85rem;
        color: var(--kk-dark);
    }
    .register-box a { color: var(--kk-orange); font-weight: 700; text-decoration: none; }
    .register-box a:hover { text-decoration: underline; }
</style>

<div class="container py-5" style="max-width: 440px;">
    <div class="login-card">

        {{-- Header --}}
        <div class="login-header">
            <div class="brand-emoji">🍽️</div>
            <h1>KhaiKhai-তে লগইন</h1>
            <p>আপনার অ্যাকাউন্টে প্রবেশ করুন</p>
        </div>

        {{-- Body --}}
        <div class="login-body">

            {{-- Error message --}}
            @if($errorMsg)
                <div class="error-alert">
                    <span>⚠️</span>
                    <span>{{ $errorMsg }}</span>
                </div>
            @endif

            {{-- Session error (e.g. from middleware) --}}
            @if(session('error'))
                <div class="error-alert">
                    <span>⚠️</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <div class="row g-3">

                {{-- Phone --}}
                <div class="col-12">
                    <label class="form-label">মোবাইল নম্বর</label>
                    <div class="input-group">
                        <span class="input-group-text">📱</span>
                        <input type="tel"
                            class="form-control @error('phone') is-invalid @enderror"
                            wire:model.live.debounce.400ms="phone"
                            placeholder="01XXXXXXXXX"
                            maxlength="11"
                            autofocus>
                    </div>
                    @error('phone')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="col-12">
                    <div class="d-flex justify-content-between">
                        <label class="form-label">পাসওয়ার্ড</label>
                        <a href="{{ route('password.request') }}"
                            style="font-size:.78rem; color:var(--kk-orange); text-decoration:none;">
                            পাসওয়ার্ড ভুলে গেছেন?
                        </a>
                    </div>
                    <div class="pw-wrap">
                        <div class="input-group">
                            <span class="input-group-text">🔒</span>
                            <input type="password"
                                id="pwField"
                                class="form-control @error('password') is-invalid @enderror"
                                wire:model.live="password"
                                placeholder="আপনার পাসওয়ার্ড">
                        </div>
                        <button type="button" class="pw-toggle" onclick="togglePw()" id="pwToggleBtn" aria-label="পাসওয়ার্ড দেখুন">
                            👁️
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                            wire:model="remember" id="rememberMe"
                            style="accent-color: var(--kk-orange);">
                        <label class="form-check-label" for="rememberMe"
                            style="font-size:.85rem; color:#6C757D; cursor:pointer;">
                            আমাকে মনে রাখুন
                        </label>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="col-12 mt-2">
                    <button type="button" class="btn-kk"
                        wire:click="login"
                        wire:loading.attr="disabled"
                        wire:target="login">
                        <span wire:loading.remove wire:target="login">
                            🚀 লগইন করুন
                        </span>
                        <span wire:loading wire:target="login">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            যাচাই হচ্ছে...
                        </span>
                    </button>
                </div>

            </div>{{-- row --}}

            <div class="divider">অথবা</div>

            {{-- Register link --}}
            <div class="register-box">
                রেস্টুরেন্ট মালিক?
                <a href="{{ route('vendor.register') }}">এখানে রেজিস্ট্রেশন করুন</a>
                <br>
                <span style="font-size:.78rem; color:#ADB5BD; margin-top:4px; display:block;">
                    Rider হতে চান?
                    <a href="{{ route('rider.register') }}">Rider অ্যাপ</a>
                </span>
            </div>

        </div>{{-- login-body --}}
    </div>{{-- login-card --}}

    <p class="text-center mt-3" style="font-size:.75rem; color:#ADB5BD;">
        © {{ date('Y') }} KhaiKhai · সকল অধিকার সংরক্ষিত
    </p>
</div>

<script>
function togglePw() {
    const f = document.getElementById('pwField');
    const b = document.getElementById('pwToggleBtn');
    if (f.type === 'password') {
        f.type = 'text';
        b.textContent = '🙈';
    } else {
        f.type = 'password';
        b.textContent = '👁️';
    }
}
</script>
</div>