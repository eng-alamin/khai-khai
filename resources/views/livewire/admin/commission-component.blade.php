<div>

    {{-- ── Flash ── --}}
    @if(session('success'))
        <div class="comm-alert comm-alert-success">
            <span class="material-icons-round">check_circle</span>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="comm-alert-close">&times;</button>
        </div>
    @endif

    {{-- ── Config Card ── --}}
    <div class="comm-card">

        <div class="comm-card-title">Commission Configuration</div>

        <form wire:submit.prevent="save">
            <div class="comm-grid">

                <div class="comm-field">
                    <label class="comm-label">Default Commission Rate</label>
                    <div class="comm-input-wrap">
                        <input type="text"
                               class="comm-input @error('defaultCommissionRate') comm-input-error @enderror"
                               wire:model.defer="defaultCommissionRate"
                               placeholder="e.g. 12">
                        <span class="comm-input-suffix">%</span>
                    </div>
                    @error('defaultCommissionRate') <span class="comm-error">{{ $message }}</span> @enderror
                </div>

                <div class="comm-field">
                    <label class="comm-label">Delivery Charge</label>
                    <div class="comm-input-wrap">
                        <span class="comm-input-prefix">৳</span>
                        <input type="text"
                               class="comm-input comm-input-prefixed @error('deliveryCharge') comm-input-error @enderror"
                               wire:model.defer="deliveryCharge"
                               placeholder="e.g. 49">
                    </div>
                    @error('deliveryCharge') <span class="comm-error">{{ $message }}</span> @enderror
                </div>

                <div class="comm-field">
                    <label class="comm-label">Premium Vendor Rate</label>
                    <div class="comm-input-wrap">
                        <input type="text"
                               class="comm-input @error('premiumVendorRate') comm-input-error @enderror"
                               wire:model.defer="premiumVendorRate"
                               placeholder="e.g. 8">
                        <span class="comm-input-suffix">%</span>
                    </div>
                    @error('premiumVendorRate') <span class="comm-error">{{ $message }}</span> @enderror
                </div>

                <div class="comm-field">
                    <label class="comm-label">Payment Cycle</label>
                    <select class="comm-select @error('paymentCycle') comm-input-error @enderror"
                            wire:model.defer="paymentCycle">
                        @foreach($paymentCycleOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('paymentCycle') <span class="comm-error">{{ $message }}</span> @enderror
                </div>

            </div>

            <button type="submit" class="comm-save-btn" wire:loading.attr="disabled" wire:target="save">
                <span class="material-icons-round" wire:loading.remove wire:target="save">save</span>
                <span class="material-icons-round comm-spin" wire:loading wire:target="save">progress_activity</span>
                Save
            </button>
        </form>

    </div>

</div>

@push('styles')
<style>
    .comm-card {
        margin: 20px;
        background: var(--card-bg); border-radius: var(--radius-lg);
        border: 1.5px solid var(--border); box-shadow: var(--shadow-card);
        padding: 24px 26px 26px;
    }
    .comm-card-title {
        font-size: 1.1rem; font-weight: 800; color: var(--dark);
        font-family: var(--font); margin-bottom: 22px;
    }

    .comm-grid {
        display: grid; grid-template-columns: repeat(2, 1fr);
        gap: 20px 24px; margin-bottom: 24px;
    }
    .comm-field { display: flex; flex-direction: column; }
    .comm-label {
        font-size: .78rem; font-weight: 600; color: var(--muted);
        font-family: var(--font); margin-bottom: 8px;
    }

    .comm-input-wrap { position: relative; display: flex; align-items: center; }
    .comm-input {
        width: 100%; padding: 10px 14px;
        border: 1.5px solid var(--border); border-radius: var(--radius-sm);
        font-family: var(--font); font-size: .88rem; color: var(--dark);
        background: var(--bg); outline: none; transition: var(--transition);
        box-sizing: border-box;
    }
    .comm-input:focus { border-color: var(--pink); box-shadow: 0 0 0 3px rgba(255,61,139,.1); }
    .comm-input-error { border-color: #e11d48; }
    .comm-input-prefixed { padding-left: 30px; }
    .comm-input-prefix {
        position: absolute; left: 12px; color: var(--muted);
        font-size: .88rem; font-family: var(--font); pointer-events: none;
    }
    .comm-input-suffix {
        position: absolute; right: 12px; color: var(--muted);
        font-size: .88rem; font-family: var(--font); pointer-events: none;
    }

    .comm-select {
        width: 100%; padding: 10px 14px;
        border: 1.5px solid var(--border); border-radius: var(--radius-sm);
        font-family: var(--font); font-size: .88rem; color: var(--dark);
        background: var(--bg); outline: none; cursor: pointer; transition: var(--transition);
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
    }
    .comm-select:focus { border-color: var(--pink); }

    .comm-error {
        color: #e11d48; font-size: .72rem; margin-top: 5px;
        font-family: var(--font);
    }

    .comm-save-btn {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--pink); color: #fff; border: none;
        border-radius: var(--radius-sm); padding: 10px 22px;
        font-size: .84rem; font-weight: 700; font-family: var(--font);
        cursor: pointer; transition: var(--transition);
    }
    .comm-save-btn:hover { opacity: .92; }
    .comm-save-btn:disabled { opacity: .7; cursor: not-allowed; }
    .comm-save-btn .material-icons-round { font-size: 1.05rem; }
    .comm-spin { animation: comm-spin-anim 0.8s linear infinite; }
    @keyframes comm-spin-anim { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    .comm-alert {
        display: flex; align-items: center; gap: 8px;
        margin: 12px 20px; padding: 12px 14px;
        border-radius: var(--radius-md); font-size: .82rem;
    }
    .comm-alert span:nth-child(2) { flex: 1; }
    .comm-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
    .comm-alert-close { background: none; border: none; cursor: pointer; font-size: 1.1rem; color: inherit; padding: 0; }

    @media (max-width: 640px) {
        .comm-card { margin: 14px; padding: 18px 16px 20px; }
        .comm-grid { grid-template-columns: 1fr; gap: 16px; }
    }
</style>
@endpush