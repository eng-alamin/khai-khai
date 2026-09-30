<div>

    <div class="settings-grid">

        {{-- ── General Settings Card ── --}}
        <div class="panel">
            <div class="panel-title">General Settings</div>

            @if(session('success_general'))
                <div class="alert alert-success">
                    <span class="material-icons-round">check_circle</span>
                    <span>{{ session('success_general') }}</span>
                    <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
                </div>
            @endif

            <form wire:submit.prevent="saveGeneral">

                <div class="form-group">
                    <label class="form-label">Platform Name</label>
                    <input type="text"
                           class="form-control @error('platformName') is-invalid @enderror"
                           wire:model.defer="platformName">
                    @error('platformName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Support Email</label>
                    <input type="email"
                           class="form-control @error('supportEmail') is-invalid @enderror"
                           wire:model.defer="supportEmail">
                    @error('supportEmail') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Helpline</label>
                    <input type="text"
                           class="form-control @error('helpline') is-invalid @enderror"
                           wire:model.defer="helpline">
                    @error('helpline') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="save-btn" wire:loading.attr="disabled" wire:target="saveGeneral">
                    <span wire:loading wire:target="saveGeneral" class="spinner-sm"></span>
                    <span class="material-icons-round" wire:loading.remove wire:target="saveGeneral">save</span>
                    Save
                </button>
            </form>
        </div>

        {{-- ── Delivery Charge Settings Card ── --}}
        <div class="panel">
            <div class="panel-title">Delivery Charge Settings</div>

            @if(session('success_delivery'))
                <div class="alert alert-success">
                    <span class="material-icons-round">check_circle</span>
                    <span>{{ session('success_delivery') }}</span>
                    <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
                </div>
            @endif

            @if(session('error_delivery'))
                <div class="alert alert-error">
                    <span class="material-icons-round">error</span>
                    <span>{{ session('error_delivery') }}</span>
                    <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
                </div>
            @endif

            <form wire:submit.prevent="saveDeliverySettings">

                <div class="form-group">
                    <label class="form-label">Minimum Delivery Charge (৳)</label>
                    <input type="number" step="0.01" min="0"
                           class="form-control @error('minimumDeliveryCharge') is-invalid @enderror"
                           wire:model.defer="minimumDeliveryCharge">
                    @error('minimumDeliveryCharge') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Per KM Delivery Rate (৳)</label>
                    <input type="number" step="0.01" min="0"
                           class="form-control @error('perKmDeliveryRate') is-invalid @enderror"
                           wire:model.defer="perKmDeliveryRate">
                    @error('perKmDeliveryRate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Included Distance in Minimum Charge (KM)</label>
                    <input type="number" step="0.1" min="0"
                           class="form-control @error('includedKmInMinimum') is-invalid @enderror"
                           wire:model.defer="includedKmInMinimum">
                    @error('includedKmInMinimum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="save-btn" wire:loading.attr="disabled" wire:target="saveDeliverySettings">
                    <span wire:loading wire:target="saveDeliverySettings" class="spinner-sm"></span>
                    <span class="material-icons-round" wire:loading.remove wire:target="saveDeliverySettings">save</span>
                    Save
                </button>
            </form>
        </div>

        {{-- ── Commission Settings Card ── --}}
        <div class="panel">
            <div class="panel-title">Commission Settings</div>

            @if(session('success_commission'))
                <div class="alert alert-success">
                    <span class="material-icons-round">check_circle</span>
                    <span>{{ session('success_commission') }}</span>
                    <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
                </div>
            @endif

            @if(session('error_commission'))
                <div class="alert alert-error">
                    <span class="material-icons-round">error</span>
                    <span>{{ session('error_commission') }}</span>
                    <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
                </div>
            @endif

            <form wire:submit.prevent="saveCommissionSettings">

                <div class="form-group">
                    <label class="form-label">Default Commission Rate (%)</label>
                    <input type="number" step="0.01" min="0" max="100"
                           class="form-control @error('defaultCommissionRate') is-invalid @enderror"
                           wire:model.defer="defaultCommissionRate">
                    @error('defaultCommissionRate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Premium Vendor Rate (%)</label>
                    <input type="number" step="0.01" min="0" max="100"
                           class="form-control @error('premiumVendorRate') is-invalid @enderror"
                           wire:model.defer="premiumVendorRate">
                    @error('premiumVendorRate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Cycle</label>
                    <select class="form-control @error('paymentCycle') is-invalid @enderror"
                            wire:model.defer="paymentCycle">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                    @error('paymentCycle') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="save-btn" wire:loading.attr="disabled" wire:target="saveCommissionSettings">
                    <span wire:loading wire:target="saveCommissionSettings" class="spinner-sm"></span>
                    <span class="material-icons-round" wire:loading.remove wire:target="saveCommissionSettings">save</span>
                    Save
                </button>
            </form>
        </div>

    </div>

</div>
