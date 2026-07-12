{{-- resources/views/livewire/reset-password.blade.php --}}
<div>
<style>
    :root {
        --pink: #e91e8c;
        --kk-purple-dark: #3d0a6e;
        --kk-purple: #7c1e8c;
        --kk-dark: #1A1A2E;
        --kk-border: #E8E8F0;
    }
    body {
        background: linear-gradient(135deg, #3d0a6e 0%, #7c1e8c 50%, var(--pink) 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', sans-serif;
    }
    .login-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 8px 40px rgba(26,26,46,.25);
        border: 1px solid var(--kk-border);
        overflow: hidden;
    }
    .login-header {
        background: linear-gradient(135deg, var(--kk-purple-dark) 0%, var(--kk-purple) 50%, var(--pink) 100%);
        padding: 2rem;
        text-align: center;
    }
    .login-header .brand-emoji { font-size: 2.5rem; }
    .login-header h1 { color: #fff; font-weight: 700; font-size: 1.4rem; margin: .4rem 0 .25rem; }
    .login-header p  { color: rgba(255,255,255,.85); font-size: .85rem; margin: 0; }
    .login-body { padding: 2rem; }
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
        border-color: var(--pink);
        box-shadow: 0 0 0 3px rgba(233,30,140,.15);
        background: #fff;
        outline: none;
    }
    .form-control.is-invalid { border-color: #DC3545; background: #FFF5F5; }
    .form-control[readonly] { background: #F1F1F5; color: #6C757D; }
    .input-group-text {
        background: #FCE9F4;
        border: 1.5px solid var(--kk-border);
        border-right: none;
        color: var(--pink);
        border-radius: 10px 0 0 10px;
        font-size: 1rem;
    }
    .input-group .form-control { border-radius: 0 10px 10px 0; }
    .invalid-feedback { font-size: .78rem; }
    .pw-wrap { position: relative; }
    .pw-toggle {
        position: absolute; right: 12px; top: 50%;
        transform: translateY(-50%);
        background: none; border: none;
        color: #ADB5BD; cursor: pointer; font-size: 1rem;
        padding: 0; line-height: 1;
    }
    .pw-toggle:hover { color: var(--pink); }
    .error-alert {
        background: #FFF0F0; border: 1px solid #FFCDD2;
        border-radius: 10px; padding: .85rem 1rem;
        font-size: .85rem; color: #C62828;
        display: flex; gap: 8px; align-items: flex-start;
        margin-bottom: 1.2rem;
    }
    .status-alert {
        background: #F0FFF4; border: 1px solid #C6F6D5;
        border-radius: 10px; padding: .85rem 1rem;
        font-size: .85rem; color: #2E7D32;
        display: flex; gap: 8px; align-items: flex-start;
        margin-bottom: 1.2rem;
    }
    .btn-kk {
        background: linear-gradient(135deg, var(--kk-purple-dark) 0%, var(--kk-purple) 50%, var(--pink) 100%);
        color: #fff; border: none; border-radius: 12px;
        padding: .8rem; font-weight: 700; font-size: 1rem;
        width: 100%; transition: all .2s;
        box-shadow: 0 4px 15px rgba(233,30,140,.3);
    }
    .btn-kk:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(233,30,140,.4);
        color: #fff;
    }
    .btn-kk:disabled { opacity: .65; transform: none; }
    .back-link {
        display: block; text-align: center; margin-top: 1.2rem;
        font-size: .85rem; color: var(--pink); text-decoration: none; font-weight: 600;
    }
    .back-link:hover { text-decoration: underline; }
</style>

<div class="container py-5" style="max-width: 440px;">
    <div class="login-card">

        <div class="login-header">
            <div class="brand-emoji">🔐</div>
            <h1>Reset Password</h1>
            <p>Choose a new password for your account</p>
        </div>

        <div class="login-body">

            @if($errorMsg)
                <div class="error-alert">
                    <span>⚠️</span>
                    <span>{{ $errorMsg }}</span>
                </div>
            @endif

            @if($statusMsg)
                <div class="status-alert">
                    <span>✅</span>
                    <span>{{ $statusMsg }}</span>
                </div>
            @endif

            <div class="row g-3">

                {{-- Email (readonly, pre-filled from link) --}}
                <div class="col-12">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text">✉️</span>
                        <input type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            wire:model="email"
                            placeholder="example@email.com">
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- New Password --}}
                <div class="col-12">
                    <label class="form-label">New Password</label>
                    <div class="pw-wrap">
                        <div class="input-group">
                            <span class="input-group-text">🔒</span>
                            <input type="password"
                                id="pwField"
                                class="form-control @error('password') is-invalid @enderror"
                                wire:model.live="password"
                                placeholder="At least 8 characters">
                        </div>
                        <button type="button" class="pw-toggle" onclick="togglePw('pwField', this)" aria-label="Show password">
                            👁️
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="col-12">
                    <label class="form-label">Confirm New Password</label>
                    <div class="pw-wrap">
                        <div class="input-group">
                            <span class="input-group-text">🔒</span>
                            <input type="password"
                                id="pwConfirmField"
                                class="form-control"
                                wire:model.live="password_confirmation"
                                placeholder="Re-enter new password">
                        </div>
                        <button type="button" class="pw-toggle" onclick="togglePw('pwConfirmField', this)" aria-label="Show password">
                            👁️
                        </button>
                    </div>
                </div>

                <div class="col-12 mt-2">
                    <button type="button" class="btn-kk"
                        wire:click="resetPassword"
                        wire:loading.attr="disabled"
                        wire:target="resetPassword">
                        <span wire:loading.remove wire:target="resetPassword">
                            🔁 Reset Password
                        </span>
                        <span wire:loading wire:target="resetPassword">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Resetting...
                        </span>
                    </button>
                </div>

            </div>

            <a href="{{ route('login') }}" class="back-link" wire:navigate>
                ← Back to Login
            </a>

        </div>
    </div>

    <p class="text-center mt-3" style="font-size:.75rem; color:#f0e0f0;">
        © {{ date('Y') }} KhaiKhai · All Rights Reserved
    </p>
</div>

<script>
function togglePw(fieldId, btn) {
    const f = document.getElementById(fieldId);
    if (f.type === 'password') {
        f.type = 'text';
        btn.textContent = '🙈';
    } else {
        f.type = 'password';
        btn.textContent = '👁️';
    }
}
</script>
</div>