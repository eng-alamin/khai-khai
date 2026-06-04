{{-- resources/views/livewire/vendor/settings.blade.php --}}
<div>

    {{-- ══════════════════════════════════════
         FLASH MESSAGE
    ══════════════════════════════════════ --}}
    @if (session()->has('success'))
        <div class="item-alert item-alert-success mb-4">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ══════════════════════════════════════
         PAGE HEADER
    ══════════════════════════════════════ --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <div class="item-topbar-title" style="font-size:20px;">⚙️ Settings</div>
            <div style="font-size:13px; color:var(--muted); margin-top:2px;">
                {{ $name }} &bull; {{ $city }}
            </div>
        </div>

        {{-- Live open/close toggle --}}
        <button
            wire:click="toggleOpen"
            wire:loading.attr="disabled"
            class="btn-new-item {{ $is_open ? 'btn-open' : 'btn-closed' }}"
            style="gap:8px;"
        >
            <i class="fa {{ $is_open ? 'fa-door-open' : 'fa-door-closed' }}"></i>
            {{ $is_open ? 'Restaurant is Open' : 'Restaurant is Closed' }}
        </button>
    </div>

    {{-- ══════════════════════════════════════
         TAB NAV
    ══════════════════════════════════════ --}}
    <div class="kk-tabs mb-4">
        @foreach ([
            'basic'    => ['icon' => 'fa-store',      'label' => 'Basic Info'],
            'media'    => ['icon' => 'fa-image',       'label' => 'Logo & Banner'],
            'delivery' => ['icon' => 'fa-motorcycle', 'label' => 'Delivery'],
            'ops'      => ['icon' => 'fa-sliders-h',  'label' => 'Operations'],
        ] as $tab => $meta)
            <button
                wire:click="switchTab('{{ $tab }}')"
                class="kk-tab {{ $activeTab === $tab ? 'active' : '' }}"
            >
                <i class="fa {{ $meta['icon'] }}"></i>
                <span>{{ $meta['label'] }}</span>
            </button>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════
         FORM
    ══════════════════════════════════════ --}}
    <form wire:submit.prevent="save">

        {{-- ─────────────────────────────────
             TAB 1 — Basic Info
        ───────────────────────────────── --}}
        @if ($activeTab === 'basic')
        <div class="row g-3">

            <div class="col-12">
                <div class="card">
                    <div class="card-title mb-4">
                        <i class="fa fa-info-circle" style="color:var(--pink);"></i>
                        Restaurant Identity
                    </div>

                    <div class="item-row g-3">

                        {{-- Name --}}
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">Restaurant Name <span class="req">*</span></label>
                                <input
                                    wire:model.live.debounce.400ms="name"
                                    class="item-form-control @error('name') is-invalid @enderror"
                                    placeholder="e.g. Ma's Kitchen"
                                >
                                @error('name')
                                    <div class="item-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Slug --}}
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">
                                    URL Slug <span class="req">*</span>
                                    <span class="item-form-hint" style="display:inline;">— auto-generated from name</span>
                                </label>
                                <div class="item-input-prefix">
                                    <span class="item-input-prefix-text">khaikhai/</span>
                                    <input
                                        wire:model="slug"
                                        class="item-form-control @error('slug') is-invalid @enderror"
                                        placeholder="mas-kitchen"
                                    >
                                </div>
                                @error('slug')
                                    <div class="item-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>

                    <div class="item-row g-3 mt-2">

                        {{-- Category --}}
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">Category <span class="req">*</span></label>
                                <select
                                    wire:model="category"
                                    class="item-form-control @error('category') is-invalid @enderror"
                                >
                                    <option value="">— Select —</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                                @error('category')
                                    <div class="item-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Emoji --}}
                        <div class="item-col" style="max-width:120px;">
                            <div class="item-form-group">
                                <label class="item-form-label">Emoji</label>
                                <input
                                    wire:model="emoji"
                                    class="item-form-control"
                                    placeholder="🍛"
                                    maxlength="10"
                                    style="font-size:20px; text-align:center;"
                                >
                            </div>
                        </div>

                        {{-- Tag --}}
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">
                                    Tag
                                    <span class="item-form-hint" style="display:inline;">— e.g. Best, Popular</span>
                                </label>
                                <input
                                    wire:model="tag"
                                    class="item-form-control"
                                    placeholder="Best"
                                    maxlength="40"
                                >
                            </div>
                        </div>

                        {{-- Phone --}}
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">Phone Number</label>
                                <input
                                    wire:model="phone"
                                    class="item-form-control @error('phone') is-invalid @enderror"
                                    placeholder="01700-000000"
                                    maxlength="15"
                                >
                                @error('phone')
                                    <div class="item-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Address Card --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-title mb-4">
                        <i class="fa fa-map-marker-alt" style="color:var(--pink);"></i>
                        Location & Address
                    </div>
                    <div class="item-row g-3">

                        {{-- Address --}}
                        <div class="item-col" style="flex:2;">
                            <div class="item-form-group">
                                <label class="item-form-label">Full Address <span class="req">*</span></label>
                                <textarea
                                    wire:model="address"
                                    class="item-form-control @error('address') is-invalid @enderror"
                                    rows="2"
                                    placeholder="House no., road, area..."
                                ></textarea>
                                @error('address')
                                    <div class="item-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- City --}}
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">City <span class="req">*</span></label>
                                <select
                                    wire:model="city"
                                    class="item-form-control @error('city') is-invalid @enderror"
                                >
                                    <option value="">— Select City —</option>
                                    @foreach ($cities as $c)
                                        <option value="{{ $c }}">{{ $c }}</option>
                                    @endforeach
                                </select>
                                @error('city')
                                    <div class="item-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>

                    <div class="item-row g-3 mt-2">

                        {{-- Latitude --}}
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">Latitude</label>
                                <input
                                    wire:model="latitude"
                                    type="number"
                                    step="0.0000001"
                                    class="item-form-control @error('latitude') is-invalid @enderror"
                                    placeholder="23.8103"
                                >
                                @error('latitude')
                                    <div class="item-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Longitude --}}
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">Longitude</label>
                                <input
                                    wire:model="longitude"
                                    type="number"
                                    step="0.0000001"
                                    class="item-form-control @error('longitude') is-invalid @enderror"
                                    placeholder="90.4125"
                                >
                                @error('longitude')
                                    <div class="item-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
        @endif

        {{-- ─────────────────────────────────
             TAB 2 — Logo & Banner (Media)
        ───────────────────────────────── --}}
        @if ($activeTab === 'media')
        <div class="row g-4">

            {{-- Logo --}}
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-title mb-3">
                        <i class="fa fa-image" style="color:var(--pink);"></i>
                        Restaurant Logo
                    </div>

                    <div class="item-img-preview kk-logo-preview mb-3">
                        @if ($logoUpload)
                            <img src="{{ $logoUpload->temporaryUrl() }}" alt="Logo Preview">
                        @elseif ($logo_url)
                            <img src="{{ Storage::url($logo_url) }}" alt="Logo">
                        @else
                            <div class="kk-img-placeholder">
                                <i class="fa fa-store" style="font-size:32px;color:var(--muted);"></i>
                                <span>No logo</span>
                            </div>
                        @endif
                    </div>

                    <div class="item-form-group">
                        <input type="file" wire:model="logoUpload" accept="image/*"
                            class="item-form-control @error('logoUpload') is-invalid @enderror">
                        <div wire:loading wire:target="logoUpload" class="item-upload-progress mt-2">
                            <div class="item-upload-bar"></div>
                            <small>Uploading...</small>
                        </div>
                        @error('logoUpload')
                            <div class="item-invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="item-form-hint mt-1">Recommended: 1:1 ratio, max 2 MB</div>
                    </div>
                </div>
            </div>

            {{-- Banner --}}
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-title mb-3">
                        <i class="fa fa-panorama" style="color:var(--pink);"></i>
                        Banner Image
                    </div>

                    <div class="item-img-preview kk-banner-preview mb-3">
                        @if ($bannerUpload)
                            <img src="{{ $bannerUpload->temporaryUrl() }}" alt="Banner Preview">
                        @elseif ($banner_url)
                            <img src="{{ Storage::url($banner_url) }}" alt="Banner">
                        @else
                            <div class="kk-img-placeholder">
                                <i class="fa fa-image" style="font-size:32px;color:var(--muted);"></i>
                                <span>No banner</span>
                            </div>
                        @endif
                    </div>

                    <div class="item-form-group">
                        <input type="file" wire:model="bannerUpload" accept="image/*"
                            class="item-form-control @error('bannerUpload') is-invalid @enderror">
                        <div wire:loading wire:target="bannerUpload" class="item-upload-progress mt-2">
                            <div class="item-upload-bar"></div>
                            <small>Uploading...</small>
                        </div>
                        @error('bannerUpload')
                            <div class="item-invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="item-form-hint mt-1">Recommended: 16:5 ratio (e.g. 1280×400), max 4 MB</div>
                    </div>
                </div>
            </div>

        </div>
        @endif

        {{-- ─────────────────────────────────
             TAB 3 — Delivery
        ───────────────────────────────── --}}
        @if ($activeTab === 'delivery')
        <div class="row g-3">

            <div class="col-12">
                <div class="card">
                    <div class="card-title mb-4">
                        <i class="fa fa-motorcycle" style="color:var(--pink);"></i>
                        Delivery Configuration
                    </div>
                    <div class="item-row g-3">

                        {{-- Delivery fee --}}
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">
                                    Delivery Fee <span class="req">*</span>
                                    <span class="item-form-hint" style="display:inline;">(in Taka)</span>
                                </label>
                                <div class="item-input-prefix">
                                    <span class="item-input-prefix-text">Tk</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value="{{ $delivery_fee / 100 }}"
                                        wire:change="setDeliveryFeeTaka($event.target.value)"
                                        class="item-form-control @error('delivery_fee') is-invalid @enderror"
                                        placeholder="49.00"
                                    >
                                </div>
                                @error('delivery_fee')
                                    <div class="item-invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="item-form-hint">Stored in paisa: {{ $delivery_fee }} paisa</div>
                            </div>
                        </div>

                        {{-- Min order --}}
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">
                                    Minimum Order
                                    <span class="item-form-hint" style="display:inline;">(in Taka, optional)</span>
                                </label>
                                <div class="item-input-prefix">
                                    <span class="item-input-prefix-text">Tk</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value="{{ $min_order_amount !== null ? $min_order_amount / 100 : '' }}"
                                        wire:change="setMinOrderTaka($event.target.value)"
                                        class="item-form-control @error('min_order_amount') is-invalid @enderror"
                                        placeholder="0"
                                    >
                                </div>
                                @error('min_order_amount')
                                    <div class="item-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Delivery range --}}
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">Delivery Time (minutes)</label>
                                <div class="item-row" style="gap:8px; align-items:center;">
                                    <input
                                        wire:model="avg_delivery_min"
                                        type="number"
                                        min="1"
                                        class="item-form-control @error('avg_delivery_min') is-invalid @enderror"
                                        placeholder="Min"
                                    >
                                    <span style="color:var(--muted); flex-shrink:0;">—</span>
                                    <input
                                        wire:model="avg_delivery_max"
                                        type="number"
                                        min="1"
                                        class="item-form-control @error('avg_delivery_max') is-invalid @enderror"
                                        placeholder="Max"
                                    >
                                </div>
                                @error('avg_delivery_min')
                                    <div class="item-invalid-feedback">{{ $message }}</div>
                                @enderror
                                @error('avg_delivery_max')
                                    <div class="item-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Delivery preview card --}}
            <div class="col-12">
                <div class="card" style="background:var(--pink-light); border-color:rgba(255,61,139,.2);">
                    <div class="d-flex align-items-center gap-3">
                        <div style="font-size:36px;">🛵</div>
                        <div>
                            <div style="font-weight:700; font-size:15px;">Customer View</div>
                            <div style="font-size:13px; color:var(--soft-dark); margin-top:4px;">
                                Delivery charge: <strong style="color:var(--pink);">Tk{{ number_format($delivery_fee / 100, 0) }}</strong>
                                &bull;
                                Time:
                                <strong>
                                    @if($avg_delivery_min && $avg_delivery_max)
                                        {{ $avg_delivery_min }}–{{ $avg_delivery_max }} min
                                    @elseif($avg_delivery_min)
                                        {{ $avg_delivery_min }}+ min
                                    @else
                                        Not set
                                    @endif
                                </strong>
                                @if($min_order_amount)
                                &bull; Min. order: <strong>Tk{{ number_format($min_order_amount / 100, 0) }}</strong>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        @endif

        {{-- ─────────────────────────────────
             TAB 4 — Operations
        ───────────────────────────────── --}}
        @if ($activeTab === 'ops')
        <div class="row g-3">

            {{-- Operational toggles --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-title mb-4">
                        <i class="fa fa-toggle-on" style="color:var(--pink);"></i>
                        Operation Controls
                    </div>

                    {{-- is_open --}}
                    <div class="kk-setting-row">
                        <div>
                            <div class="kk-setting-title">Restaurant is Open</div>
                            <div class="kk-setting-sub">When closed, no new orders will be accepted</div>
                        </div>
                        <label class="item-toggle">
                            <input type="checkbox" wire:model="is_open">
                            <span class="item-toggle-slider"></span>
                        </label>
                    </div>

                    {{-- is_active --}}
                    <div class="kk-setting-row">
                        <div>
                            <div class="kk-setting-title">Account Active</div>
                            <div class="kk-setting-sub">When inactive, restaurant won't appear in the app</div>
                        </div>
                        <label class="item-toggle">
                            <input type="checkbox" wire:model="is_active">
                            <span class="item-toggle-slider"></span>
                        </label>
                    </div>

                    {{-- auto_accept --}}
                    <div class="kk-setting-row">
                        <div>
                            <div class="kk-setting-title">Auto-Accept Orders</div>
                            <div class="kk-setting-sub">When on, orders are confirmed without manual approval</div>
                        </div>
                        <label class="item-toggle">
                            <input type="checkbox" wire:model="auto_accept">
                            <span class="item-toggle-slider"></span>
                        </label>
                    </div>

                    {{-- notification_sound --}}
                    <div class="kk-setting-row" style="border-bottom:none;">
                        <div>
                            <div class="kk-setting-title">Notification Sound</div>
                            <div class="kk-setting-sub">Play a sound when a new order arrives</div>
                        </div>
                        <label class="item-toggle">
                            <input type="checkbox" wire:model="notification_sound">
                            <span class="item-toggle-slider"></span>
                        </label>
                    </div>

                </div>
            </div>

            {{-- Prep time --}}
            <div class="col-md-6">
                <div class="card">
                    <div class="card-title mb-4">
                        <i class="fa fa-clock" style="color:var(--pink);"></i>
                        Preparation Time
                    </div>

                    <div class="item-form-group">
                        <label class="item-form-label">
                            Default Prep Time <span class="req">*</span>
                        </label>
                        <div class="d-flex align-items-center gap-3">
                            <input
                                wire:model="prep_time_min"
                                type="range"
                                min="5"
                                max="120"
                                step="5"
                                class="kk-range"
                                style="flex:1;"
                            >
                            <div class="kk-range-value">
                                {{ $prep_time_min }} <span>min</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between item-form-hint mt-1">
                            <span>5 min</span>
                            <span>120 min</span>
                        </div>
                        @error('prep_time_min')
                            <div class="item-invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="kk-info-box mt-3">
                        <i class="fa fa-info-circle"></i>
                        This time will be shown to customers as the estimated delivery time.
                        Delivery tracking will start from this duration.
                    </div>
                </div>

                {{-- Status summary --}}
                <div class="card mt-3">
                    <div class="card-title mb-3">
                        <i class="fa fa-clipboard-check" style="color:var(--pink);"></i>
                        Current Status
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <div class="kk-status-row">
                            <span>Restaurant Status</span>
                            <span class="item-status-badge {{ $is_open ? 'available' : 'unavailable' }}">
                                {{ $is_open ? '🟢 Open' : '🔴 Closed' }}
                            </span>
                        </div>
                        <div class="kk-status-row">
                            <span>Account</span>
                            <span class="item-status-badge {{ $is_active ? 'available' : 'unavailable' }}">
                                {{ $is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="kk-status-row">
                            <span>Order Acceptance</span>
                            <span class="item-status-badge badge-purple">
                                {{ $auto_accept ? 'Automatic' : 'Manual' }}
                            </span>
                        </div>
                        <div class="kk-status-row" style="border-bottom:none;">
                            <span>Prep Time</span>
                            <span class="item-status-badge badge-orange">{{ $prep_time_min }} min</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        @endif

        {{-- ══════════════════════════════════════
             SAVE BUTTON (always visible)
        ══════════════════════════════════════ --}}
        <div class="d-flex align-items-center justify-content-end gap-3 mt-4">
            <span wire:loading wire:target="save" style="font-size:13px; color:var(--muted);">
                <i class="fa fa-spinner fa-spin"></i> Saving...
            </span>
            <button
                type="submit"
                class="btn-new-item"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                <span wire:loading wire:target="save" class="spinner-sm"></span>
                <i class="fa fa-save"></i>
                Save Settings
            </button>
        </div>

    </form>

</div>

{{-- ══════════════════════════════════════
     COMPONENT STYLES
══════════════════════════════════════ --}}
@push('styles')
<style>

    /* ── Tabs ── */
    .kk-tabs {
        display: flex;
        gap: 4px;
        background: var(--card-bg);
        border: 1.5px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 6px;
        flex-wrap: wrap;
    }
    .kk-tab {
        flex: 1;
        min-width: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: var(--radius-md);
        border: none;
        background: transparent;
        color: var(--soft-dark);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        font-family: var(--font);
        transition: var(--transition);
    }
    .kk-tab:hover  { background: var(--pink-light); color: var(--pink); }
    .kk-tab.active {
        background: var(--pink);
        color: #fff;
        box-shadow: 0 4px 12px rgba(255,61,139,.3);
    }

            /* ── Add Item Button ── */
        .btn-new-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--pink);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 9px 18px;
            font-size: .82rem;
            font-weight: 600;
            font-family: var(--font);
            cursor: pointer;
            box-shadow: 0 4px 18px rgba(255,61,139,.35);
            transition: var(--transition);
            letter-spacing: .02em;
        }
        .btn-new-item:hover {
            background: #e02d7a;
            box-shadow: 0 6px 24px rgba(255,61,139,.45);
            transform: translateY(-1px);
        }
        .btn-new-item .plus-icon { font-size: 1.1rem; font-weight: 400; line-height: 1; }


    /* ── Open/Closed button variants ── */
    .btn-open   { background: #1A9453 !important; box-shadow: 0 4px 18px rgba(26,148,83,.3) !important; }
    .btn-closed { background: #E53935 !important; box-shadow: 0 4px 18px rgba(229,57,53,.3) !important; }
    .btn-open:hover   { background: #157a43 !important; }
    .btn-closed:hover { background: #c62828 !important; }

    /* ── Form group / label / control ── */
    .item-form-group { margin-bottom: 14px; }
    .item-form-label {
        display: block;
        font-size: .8rem;
        font-weight: 600;
        color: var(--soft-dark);
        margin-bottom: 5px;
    }
    .item-form-label .req { color: var(--pink); margin-left: 2px; }
    .item-form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-sm);
        font-family: var(--font);
        font-size: .84rem;
        color: var(--dark);
        background: var(--bg);
        outline: none;
        transition: var(--transition);
        box-sizing: border-box;
    }
    .item-form-control:focus {
        border-color: var(--pink);
        box-shadow: 0 0 0 3px rgba(255,61,139,.1);
        background: #fff;
    }
    .item-form-control.is-invalid { border-color: #E53935; }
    .item-invalid-feedback { color: #E53935; font-size: .74rem; margin-top: 4px; }
    .item-form-hint { font-size: .72rem; color: var(--muted); margin-top: 4px; }

    /* ── Prefix input ── */
    .item-input-prefix { display: flex; align-items: stretch; }
    .item-input-prefix-text {
        padding: 10px 11px;
        background: var(--border);
        border: 1.5px solid var(--border);
        border-right: none;
        border-radius: var(--radius-sm) 0 0 var(--radius-sm);
        font-size: .84rem;
        color: var(--soft-dark);
        font-weight: 600;
        white-space: nowrap;
    }
    .item-input-prefix .item-form-control {
        border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
    }

    /* ── Row / Col ── */
    .item-row { display: flex; gap: 12px; flex-wrap: wrap; }
    .item-col { flex: 1; min-width: 0; }

    /* ── Toggle ── */
    .item-toggle {
        position: relative;
        width: 44px; height: 24px;
        flex-shrink: 0;
        display: inline-block;
    }
    .item-toggle input { opacity: 0; width: 0; height: 0; }
    .item-toggle-slider {
        position: absolute;
        inset: 0;
        background: #ddd;
        border-radius: 50px;
        cursor: pointer;
        transition: var(--transition);
    }
    .item-toggle-slider::before {
        content: '';
        position: absolute;
        width: 18px; height: 18px;
        left: 3px; top: 3px;
        background: #fff;
        border-radius: 50%;
        transition: var(--transition);
        box-shadow: 0 1px 4px rgba(0,0,0,.2);
    }
    .item-toggle input:checked + .item-toggle-slider { background: var(--pink); }
    .item-toggle input:checked + .item-toggle-slider::before { transform: translateX(20px); }

    /* ── Status badges ── */
    .item-status-badge {
        padding: 3px 10px;
        border-radius: 50px;
        font-size: .68rem;
        font-weight: 600;
        font-family: var(--font);
        display: inline-block;
    }
    .item-status-badge.available   { background: #E8FAF0; color: #1A9453; }
    .item-status-badge.unavailable { background: #FFF0F0; color: #E53935; }
    .item-status-badge.badge-purple { background: #EDE9FE; color: #6D28D9; }
    .item-status-badge.badge-orange { background: #FEF3C7; color: #92400E; }

    /* ── Setting rows ── */
    .kk-setting-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 0;
        border-bottom: 1px solid var(--border);
        gap: 16px;
    }
    .kk-setting-title { font-size: 14px; font-weight: 600; color: var(--dark); }
    .kk-setting-sub   { font-size: 12px; color: var(--muted); margin-top: 2px; }

    /* ── Status rows ── */
    .kk-status-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
        color: var(--soft-dark);
    }

    /* ── Range input ── */
    .kk-range {
        -webkit-appearance: none;
        height: 6px;
        border-radius: 10px;
        background: var(--border);
        outline: none;
        cursor: pointer;
    }
    .kk-range::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 20px; height: 20px;
        border-radius: 50%;
        background: var(--pink);
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(255,61,139,.4);
    }
    .kk-range-value {
        min-width: 72px;
        text-align: center;
        font-size: 18px;
        font-weight: 800;
        color: var(--pink);
        font-family: var(--font);
        background: var(--pink-light);
        border-radius: var(--radius-sm);
        padding: 6px 10px;
        line-height: 1;
    }
    .kk-range-value span { font-size: 11px; font-weight: 600; color: var(--muted); display: block; margin-top: 2px; }

    /* ── Media image preview ── */
    .item-img-preview {
        border: 2px dashed var(--border);
        border-radius: var(--radius-md);
        overflow: hidden;
        background: var(--bg);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .kk-logo-preview   { height: 130px; }
    .kk-banner-preview { height: 130px; }
    .item-img-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .kk-img-placeholder { display: flex; flex-direction: column; align-items: center; gap: 8px; color: var(--muted); font-size: 13px; }

    /* ── Upload progress ── */
    .item-upload-progress { margin-top: 8px; }
    .item-upload-bar {
        height: 4px;
        border-radius: 99px;
        background: linear-gradient(90deg, var(--pink), #ff8fab);
        background-size: 200% 100%;
        animation: shimmer 1.2s infinite;
        margin-bottom: 4px;
    }
    @keyframes shimmer {
        0%   { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .item-upload-progress small { font-size: .72rem; color: var(--muted); }

    /* ── Info box ── */
    .kk-info-box {
        background: #DBEAFE;
        color: #1E40AF;
        border-radius: var(--radius-sm);
        padding: 12px 14px;
        font-size: 12px;
        line-height: 1.6;
        display: flex;
        gap: 8px;
        align-items: flex-start;
    }

    /* ── Alert ── */
    .item-alert {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 14px;
        border-radius: var(--radius-md);
        font-size: .82rem;
    }
    .item-alert span { flex: 1; }
    .item-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
    .item-alert-error   { background: #FFF0F0; color: #E53935; border: 1px solid #FFBCBC; }

    /* ── Spinner ── */
    .spinner-sm {
        width: 14px; height: 14px;
        border: 2px solid rgba(255,255,255,.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin .6s linear infinite;
        display: inline-block;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 400px) {
        .item-row { flex-direction: column; }
        .kk-tabs  { gap: 2px; }
        .kk-tab   { min-width: 80px; font-size: 12px; padding: 8px 10px; }
    }

</style>
@endpush