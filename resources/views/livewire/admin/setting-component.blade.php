<div>

    <div class="set-columns">

        {{-- ── General Settings Card ── --}}
        <div class="set-card">
            <div class="set-card-title">General Settings</div>

            @if(session('success_general'))
                <div class="set-alert set-alert-success">
                    <span class="material-icons-round">check_circle</span>
                    <span>{{ session('success_general') }}</span>
                    <button onclick="this.parentElement.remove()" class="set-alert-close">&times;</button>
                </div>
            @endif

            <form wire:submit.prevent="saveGeneral">

                <div class="set-field">
                    <label class="set-label">Platform Name</label>
                    <input type="text"
                           class="set-input @error('platformName') set-input-error @enderror"
                           wire:model.defer="platformName">
                    @error('platformName') <span class="set-error">{{ $message }}</span> @enderror
                </div>

                <div class="set-field">
                    <label class="set-label">Support Email</label>
                    <input type="email"
                           class="set-input @error('supportEmail') set-input-error @enderror"
                           wire:model.defer="supportEmail">
                    @error('supportEmail') <span class="set-error">{{ $message }}</span> @enderror
                </div>

                <div class="set-field">
                    <label class="set-label">Helpline</label>
                    <input type="text"
                           class="set-input @error('helpline') set-input-error @enderror"
                           wire:model.defer="helpline">
                    @error('helpline') <span class="set-error">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="set-save-btn" wire:loading.attr="disabled" wire:target="saveGeneral">
                    <span class="material-icons-round" wire:loading.remove wire:target="saveGeneral">save</span>
                    <span class="material-icons-round set-spin" wire:loading wire:target="saveGeneral">progress_activity</span>
                    Save
                </button>
            </form>
        </div>

        {{-- ── Delivery Charge Settings Card ── --}}
        <div class="set-card">
            <div class="set-card-title">Delivery Charge Settings</div>

            @if(session('success_delivery'))
                <div class="set-alert set-alert-success">
                    <span class="material-icons-round">check_circle</span>
                    <span>{{ session('success_delivery') }}</span>
                    <button onclick="this.parentElement.remove()" class="set-alert-close">&times;</button>
                </div>
            @endif

            @if(session('error_delivery'))
                <div class="set-alert set-alert-error">
                    <span class="material-icons-round">error</span>
                    <span>{{ session('error_delivery') }}</span>
                    <button onclick="this.parentElement.remove()" class="set-alert-close">&times;</button>
                </div>
            @endif

            <form wire:submit.prevent="saveDeliverySettings">

                <div class="set-field">
                    <label class="set-label">Minimum Delivery Charge (৳)</label>
                    <input type="number" step="0.01" min="0"
                           class="set-input @error('minimumDeliveryCharge') set-input-error @enderror"
                           wire:model.defer="minimumDeliveryCharge">
                    @error('minimumDeliveryCharge') <span class="set-error">{{ $message }}</span> @enderror
                </div>

                <div class="set-field">
                    <label class="set-label">Per KM Delivery Rate (৳)</label>
                    <input type="number" step="0.01" min="0"
                           class="set-input @error('perKmDeliveryRate') set-input-error @enderror"
                           wire:model.defer="perKmDeliveryRate">
                    @error('perKmDeliveryRate') <span class="set-error">{{ $message }}</span> @enderror
                </div>

                <div class="set-field">
                    <label class="set-label">Included Distance in Minimum Charge (KM)</label>
                    <input type="number" step="0.1" min="0"
                           class="set-input @error('includedKmInMinimum') set-input-error @enderror"
                           wire:model.defer="includedKmInMinimum">
                    @error('includedKmInMinimum') <span class="set-error">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="set-save-btn" wire:loading.attr="disabled" wire:target="saveDeliverySettings">
                    <span class="material-icons-round" wire:loading.remove wire:target="saveDeliverySettings">save</span>
                    <span class="material-icons-round set-spin" wire:loading wire:target="saveDeliverySettings">progress_activity</span>
                    Save
                </button>
            </form>
        </div>

        {{-- ── Commission Settings Card ── --}}
        <div class="set-card">
            <div class="set-card-title">Commission Settings</div>

            @if(session('success_commission'))
                <div class="set-alert set-alert-success">
                    <span class="material-icons-round">check_circle</span>
                    <span>{{ session('success_commission') }}</span>
                    <button onclick="this.parentElement.remove()" class="set-alert-close">&times;</button>
                </div>
            @endif

            @if(session('error_commission'))
                <div class="set-alert set-alert-error">
                    <span class="material-icons-round">error</span>
                    <span>{{ session('error_commission') }}</span>
                    <button onclick="this.parentElement.remove()" class="set-alert-close">&times;</button>
                </div>
            @endif

            <form wire:submit.prevent="saveCommissionSettings">

                <div class="set-field">
                    <label class="set-label">Default Commission Rate (%)</label>
                    <input type="number" step="0.01" min="0" max="100"
                           class="set-input @error('defaultCommissionRate') set-input-error @enderror"
                           wire:model.defer="defaultCommissionRate">
                    @error('defaultCommissionRate') <span class="set-error">{{ $message }}</span> @enderror
                </div>

                <div class="set-field">
                    <label class="set-label">Premium Vendor Rate (%)</label>
                    <input type="number" step="0.01" min="0" max="100"
                           class="set-input @error('premiumVendorRate') set-input-error @enderror"
                           wire:model.defer="premiumVendorRate">
                    @error('premiumVendorRate') <span class="set-error">{{ $message }}</span> @enderror
                </div>

                <div class="set-field">
                    <label class="set-label">Payment Cycle</label>
                    <select class="set-input @error('paymentCycle') set-input-error @enderror"
                            wire:model.defer="paymentCycle">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                    @error('paymentCycle') <span class="set-error">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="set-save-btn" wire:loading.attr="disabled" wire:target="saveCommissionSettings">
                    <span class="material-icons-round" wire:loading.remove wire:target="saveCommissionSettings">save</span>
                    <span class="material-icons-round set-spin" wire:loading wire:target="saveCommissionSettings">progress_activity</span>
                    Save
                </button>
            </form>
        </div>

    </div>

</div>

@push('styles')
<style>
    .set-columns {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px; padding: 20px;
    }
    .set-card {
        background: var(--card-bg); border-radius: var(--radius-lg);
        border: 1.5px solid var(--border); box-shadow: var(--shadow-card);
        padding: 24px 26px 26px;
    }
    .set-card-title {
        font-size: 1.1rem; font-weight: 800; color: var(--dark);
        font-family: var(--font); margin-bottom: 20px;
    }

    .set-field { display: flex; flex-direction: column; margin-bottom: 18px; }
    .set-label {
        font-size: .78rem; font-weight: 600; color: var(--muted);
        font-family: var(--font); margin-bottom: 8px;
    }
    .set-input {
        width: 100%; padding: 10px 14px;
        border: 1.5px solid var(--border); border-radius: var(--radius-sm);
        font-family: var(--font); font-size: .88rem; color: var(--dark);
        background: var(--bg); outline: none; transition: var(--transition);
        box-sizing: border-box;
    }
    .set-input:focus { border-color: var(--pink); box-shadow: 0 0 0 3px rgba(255,61,139,.1); }
    .set-input-error { border-color: #e11d48; }
    .set-error {
        color: #e11d48; font-size: .72rem; margin-top: 5px;
        font-family: var(--font);
    }

    .set-checkbox-wrap {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: .86rem; color: var(--dark); font-family: var(--font);
        cursor: pointer;
    }
    .set-checkbox-wrap input[type="checkbox"] {
        width: 16px; height: 16px; accent-color: var(--pink); cursor: pointer;
    }

    .set-save-btn, .set-update-btn {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--pink); color: #fff; border: none;
        border-radius: var(--radius-sm); padding: 10px 22px;
        font-size: .84rem; font-weight: 700; font-family: var(--font);
        cursor: pointer; transition: var(--transition); margin-top: 4px;
    }
    .set-save-btn:hover, .set-update-btn:hover { opacity: .92; }
    .set-save-btn:disabled, .set-update-btn:disabled { opacity: .7; cursor: not-allowed; }
    .set-save-btn .material-icons-round, .set-update-btn .material-icons-round { font-size: 1.05rem; }
    .set-spin { animation: set-spin-anim 0.8s linear infinite; }
    @keyframes set-spin-anim { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    .set-alert {
        display: flex; align-items: center; gap: 8px;
        margin-bottom: 16px; padding: 12px 14px;
        border-radius: var(--radius-md); font-size: .82rem;
    }
    .set-alert span:nth-child(2) { flex: 1; }
    .set-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
    .set-alert-error { background: #FDECEC; color: #B91C1C; border: 1px solid #F5B5B5; }
    .set-alert-close { background: none; border: none; cursor: pointer; font-size: 1.1rem; color: inherit; padding: 0; }

    @media (max-width: 768px) {
        .set-columns { grid-template-columns: 1fr; padding: 14px; gap: 14px; }
        .set-card { padding: 18px 16px 20px; }
    }
</style>
@endpush