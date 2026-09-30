@once
  @push('styles')
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""
    />
  @endpush
@endonce

{{-- resources/views/livewire/vendor/settings.blade.php --}}
<div>

    {{-- ══════════════════════════════════════
         FLASH MESSAGE
    ══════════════════════════════════════ --}}
    @if (session()->has('success'))
        <div class="alert alert-success m-0 mb-4">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ══════════════════════════════════════
         PAGE HEADER
    ══════════════════════════════════════ --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <div class="topbar-title" style="font-size:20px;">⚙️ Settings</div>
            <div style="font-size:13px; color:var(--muted); margin-top:2px;">
                {{ $name }} &bull; {{ $city }}
            </div>
        </div>

        {{-- Live open/close toggle --}}
        <button
            wire:click="toggleOpen"
            wire:loading.attr="disabled"
            class="btn-new-adm {{ $is_open ? 'btn-open' : 'btn-closed' }}"
            style="gap:8px;"
        >
            <i class="fa {{ $is_open ? 'fa-door-open' : 'fa-door-closed' }}"></i>
            {{ $is_open ? 'Restaurant is Open' : 'Restaurant is Closed' }}
        </button>
    </div>

    {{-- ══════════════════════════════════════
         TAB NAV
    ══════════════════════════════════════ --}}
    <div class="settings-tabs mb-4">
        @foreach ([
            'basic'    => ['icon' => 'fa-store',      'label' => 'Basic Info'],
            'media'    => ['icon' => 'fa-image',       'label' => 'Logo & Banner'],
            'ops'      => ['icon' => 'fa-sliders-h',  'label' => 'Operations'],
        ] as $tab => $meta)
            <button
                wire:click="switchTab('{{ $tab }}')"
                class="settings-tab {{ $activeTab === $tab ? 'active' : '' }}"
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

                    <div class="settings-row g-3">

                        {{-- Name --}}
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Restaurant Name <span class="req">*</span></label>
                                <input
                                    wire:model.live.debounce.400ms="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    placeholder="e.g. Ma's Kitchen"
                                >
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Slug --}}
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">
                                    URL Slug <span class="req">*</span>
                                    <span class="form-hint" style="display:inline;">— auto-generated from name</span>
                                </label>
                                <div class="input-prefix">
                                    <span class="input-prefix-text">khaikhai/</span>
                                    <input
                                        wire:model="slug"
                                        class="form-control @error('slug') is-invalid @enderror"
                                        placeholder="mas-kitchen"
                                    >
                                </div>
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>

                    <div class="settings-row g-3 mt-2">

                        {{-- Category --}}
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Category <span class="req">*</span></label>
                                <select
                                    wire:model="category"
                                    class="form-control @error('category') is-invalid @enderror"
                                >
                                    <option value="">— Select —</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Emoji --}}
                        <div class="col" style="max-width:120px;">
                            <div class="form-group">
                                <label class="form-label">Emoji</label>
                                <input
                                    wire:model="emoji"
                                    class="form-control"
                                    placeholder="🍛"
                                    maxlength="10"
                                    style="font-size:20px; text-align:center;"
                                >
                            </div>
                        </div>

                        {{-- Tag --}}
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">
                                    Tag
                                    <span class="form-hint" style="display:inline;">— e.g. Best, Popular</span>
                                </label>
                                <input
                                    wire:model="tag"
                                    class="form-control"
                                    placeholder="Best"
                                    maxlength="40"
                                >
                            </div>
                        </div>

                        {{-- Phone --}}
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input
                                    wire:model="phone"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    placeholder="01700-000000"
                                    maxlength="15"
                                >
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
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
                    <div class="settings-row g-3">

                        {{-- Address --}}
                        <div class="col" style="flex:2;">
                            <div class="form-group">
                                <label class="form-label">Full Address <span class="req">*</span></label>
                                <textarea
                                    wire:model="address"
                                    class="form-control @error('address') is-invalid @enderror"
                                    rows="2"
                                    placeholder="House no., road, area..."
                                ></textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- City --}}
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">City <span class="req">*</span></label>
                                <select
                                    wire:model="city"
                                    class="form-control @error('city') is-invalid @enderror"
                                >
                                    <option value="">— Select City —</option>
                                    @foreach ($cities as $c)
                                        <option value="{{ $c }}">{{ $c }}</option>
                                    @endforeach
                                </select>
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>

                    {{-- Map Picker --}}
                    <div class="form-group mt-2 mb-2">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label mb-0">Pin Location on Map</label>
                            <button
                                type="button"
                                id="useCurrentLocationBtnSettings"
                                class="btn-mini"
                            >
                                <i class="fa fa-location-crosshairs"></i> Use my location
                            </button>
                        </div>

                        <div
                            id="settingsMap"
                            wire:ignore
                            style="height:240px; width:100%; border-radius:var(--radius-md); border:1.5px solid var(--border); overflow:hidden;"
                        ></div>

                        <div style="font-size:12px; color:var(--muted); margin-top:6px;">
                            <i class="fa fa-map-marker-alt"></i>
                            @if($latitude && $longitude)
                                Selected: {{ number_format($latitude, 6) }}, {{ number_format($longitude, 6) }}
                            @else
                                Click on the map (or drag the marker) to select the restaurant's exact location.
                            @endif
                        </div>
                    </div>

                    {{-- Latitude / Longitude (auto-filled by map, still editable) --}}
                    <div class="settings-row g-3 mt-2">

                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Latitude</label>
                                <input
                                    wire:model="latitude"
                                    type="number"
                                    step="0.0000001"
                                    class="form-control @error('latitude') is-invalid @enderror"
                                    placeholder="23.8103"
                                >
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Longitude</label>
                                <input
                                    wire:model="longitude"
                                    type="number"
                                    step="0.0000001"
                                    class="form-control @error('longitude') is-invalid @enderror"
                                    placeholder="90.4125"
                                >
                                @error('longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
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

                    <div class="media-preview logo-preview mb-3">
                        @if ($logoUpload)
                            <img src="{{ $logoUpload->temporaryUrl() }}" alt="Logo Preview">
                        @elseif ($logo_url)
                            <img src="{{ asset($logo_url) }}" alt="Logo">
                        @else
                            <div class="media-placeholder">
                                <i class="fa fa-store" style="font-size:32px;color:var(--muted);"></i>
                                <span>No logo</span>
                            </div>
                        @endif
                    </div>

                    <div class="form-group">
                        <input type="file" wire:model="logoUpload" accept="image/*"
                            class="form-control @error('logoUpload') is-invalid @enderror">
                        <div wire:loading wire:target="logoUpload" class="upload-progress mt-2">
                            <div class="upload-bar"></div>
                            <small>Uploading...</small>
                        </div>
                        @error('logoUpload')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-hint mt-1">Recommended: 1:1 ratio, max 2 MB</div>
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

                    <div class="media-preview banner-preview mb-3">
                        @if ($bannerUpload)
                            <img src="{{ $bannerUpload->temporaryUrl() }}" alt="Banner Preview">
                        @elseif ($banner_url)
                            <img src="{{ asset($banner_url) }}" alt="Banner">
                        @else
                            <div class="media-placeholder">
                                <i class="fa fa-image" style="font-size:32px;color:var(--muted);"></i>
                                <span>No banner</span>
                            </div>
                        @endif
                    </div>

                    <div class="form-group">
                        <input type="file" wire:model="bannerUpload" accept="image/*"
                            class="form-control @error('bannerUpload') is-invalid @enderror">
                        <div wire:loading wire:target="bannerUpload" class="upload-progress mt-2">
                            <div class="upload-bar"></div>
                            <small>Uploading...</small>
                        </div>
                        @error('bannerUpload')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-hint mt-1">Recommended: 16:5 ratio (e.g. 1280×400), max 4 MB</div>
                    </div>
                </div>
            </div>

        </div>
        @endif

        {{-- ─────────────────────────────────
             TAB 3 — Operations
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
                    <div class="settings-item-row">
                        <div>
                            <div class="settings-item-title">Restaurant is Open</div>
                            <div class="settings-item-sub">When closed, no new orders will be accepted</div>
                        </div>
                        <label class="settings-toggle">
                            <input type="checkbox" wire:model="is_open">
                            <span class="settings-toggle-slider"></span>
                        </label>
                    </div>

                    {{-- is_active --}}
                    <div class="settings-item-row">
                        <div>
                            <div class="settings-item-title">Account Active</div>
                            <div class="settings-item-sub">When inactive, restaurant won't appear in the app</div>
                        </div>
                        <label class="settings-toggle">
                            <input type="checkbox" wire:model="is_active">
                            <span class="settings-toggle-slider"></span>
                        </label>
                    </div>

                    {{-- auto_accept --}}
                    <div class="settings-item-row">
                        <div>
                            <div class="settings-item-title">Auto-Accept Orders</div>
                            <div class="settings-item-sub">When on, orders are confirmed without manual approval</div>
                        </div>
                        <label class="settings-toggle">
                            <input type="checkbox" wire:model="auto_accept">
                            <span class="settings-toggle-slider"></span>
                        </label>
                    </div>

                    {{-- notification_sound --}}
                    <div class="settings-item-row" style="border-bottom:none;">
                        <div>
                            <div class="settings-item-title">Notification Sound</div>
                            <div class="settings-item-sub">Play a sound when a new order arrives</div>
                        </div>
                        <label class="settings-toggle">
                            <input type="checkbox" wire:model="notification_sound">
                            <span class="settings-toggle-slider"></span>
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

                    <div class="form-group">
                        <label class="form-label">
                            Default Prep Time <span class="req">*</span>
                        </label>
                        <div class="d-flex align-items-center gap-3">
                            <input
                                wire:model="prep_time_min"
                                type="range"
                                min="5"
                                max="120"
                                step="5"
                                class="settings-range"
                                style="flex:1;"
                            >
                            <div class="settings-range-value">
                                {{ $prep_time_min }} <span>min</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between form-hint mt-1">
                            <span>5 min</span>
                            <span>120 min</span>
                        </div>
                        @error('prep_time_min')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="info-box mt-3">
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
                        <div class="settings-status-row">
                            <span>Restaurant Status</span>
                            <span class="status-badge {{ $is_open ? 'available' : 'unavailable' }}">
                                {{ $is_open ? '🟢 Open' : '🔴 Closed' }}
                            </span>
                        </div>
                        <div class="settings-status-row">
                            <span>Account</span>
                            <span class="status-badge {{ $is_active ? 'available' : 'unavailable' }}">
                                {{ $is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="settings-status-row">
                            <span>Order Acceptance</span>
                            <span class="status-badge badge-purple">
                                {{ $auto_accept ? 'Automatic' : 'Manual' }}
                            </span>
                        </div>
                        <div class="settings-status-row" style="border-bottom:none;">
                            <span>Prep Time</span>
                            <span class="status-badge badge-orange">{{ $prep_time_min }} min</span>
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
                class="btn-new-adm"
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

@once
  @push('scripts')
    <script
      src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
      crossorigin=""
    ></script>
  @endpush
@endonce

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {

        const DEFAULT_LAT = 23.8103; // Dhaka
        const DEFAULT_LNG = 90.4125;

        let settingsMap    = null;
        let settingsMarker = null;

        function placeMarker(lat, lng) {
            if (!settingsMap) return;

            if (settingsMarker) {
                settingsMarker.setLatLng([lat, lng]);
            } else {
                settingsMarker = L.marker([lat, lng], { draggable: true }).addTo(settingsMap);
                settingsMarker.on('dragend', (e) => {
                    const pos = e.target.getLatLng();
                    @this.set('latitude', pos.lat);
                    @this.set('longitude', pos.lng);
                });
            }
        }

        function initSettingsMap(lat, lng) {
            const el = document.getElementById('settingsMap');
            if (!el) return; // Basic tab not active — nothing to init

            const centerLat = lat ?? DEFAULT_LAT;
            const centerLng = lng ?? DEFAULT_LNG;

            // Leaflet keeps a private reference on the DOM node; if this
            // container was removed/re-added by Livewire's tab switch,
            // any old map instance tied to it is stale — reset it.
            if (settingsMap && settingsMap._container !== el) {
                settingsMap = null;
                settingsMarker = null;
            }

            if (!settingsMap) {
                settingsMap = L.map('settingsMap').setView([centerLat, centerLng], lat ? 16 : 12);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(settingsMap);

                settingsMap.on('click', (e) => {
                    placeMarker(e.latlng.lat, e.latlng.lng);
                    @this.set('latitude', e.latlng.lat);
                    @this.set('longitude', e.latlng.lng);
                });
            } else {
                settingsMap.setView([centerLat, centerLng], lat ? 16 : 12);
            }

            if (lat && lng) {
                placeMarker(lat, lng);
            }

            setTimeout(() => settingsMap && settingsMap.invalidateSize(), 150);
        }

        // Re-init map whenever the vendor switches back to the Basic tab
        Livewire.on('tab-switched', (payload) => {
            const data = Array.isArray(payload) ? payload[0] : payload;
            if (data?.tab !== 'basic') return;
            setTimeout(() => initSettingsMap(data?.latitude ?? null, data?.longitude ?? null), 150);
        });

        // First paint — default active tab is "basic", so the map div
        // already exists in the DOM on initial page load.
        setTimeout(() => {
            const latEl = document.querySelector('[wire\\:model="latitude"]');
            const lngEl = document.querySelector('[wire\\:model="longitude"]');
            const lat = latEl && latEl.value ? parseFloat(latEl.value) : null;
            const lng = lngEl && lngEl.value ? parseFloat(lngEl.value) : null;
            initSettingsMap(lat, lng);
        }, 200);

        // "Use my location" button
        document.addEventListener('click', (e) => {
            if (e.target.closest('#useCurrentLocationBtnSettings')) {
                if (!navigator.geolocation) {
                    alert('Geolocation is not supported by this browser.');
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const { latitude, longitude } = position.coords;
                        placeMarker(latitude, longitude);
                        if (settingsMap) settingsMap.setView([latitude, longitude], 16);
                        @this.set('latitude', latitude);
                        @this.set('longitude', longitude);
                    },
                    () => alert('Unable to fetch your current location. Please allow location access.'),
                );
            }
        });

    });
</script>
@endpush