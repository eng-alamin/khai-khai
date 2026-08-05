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

<div class="rider-setting">
  <div class="row g-3">

    {{-- SETTING CARD --}}
    <div class="col-md-6">
      <div class="card setting-card">

        {{-- Avatar & Info --}}
        <div class="setting-hero">
          <div class="setting-hero-bg"></div>

          <div class="avatar-wrap">
            @if(str_starts_with($avatar, 'http') || str_starts_with($avatar, '/'))
              <img
                src="{{ $avatar }}"
                class="avatar-img"
                alt="{{ $name }}"
              >
            @else
              <div class="avatar-fallback">
                {{ $avatar }}
              </div>
            @endif
            <span class="avatar-status-dot {{ $isOnline ? 'is-online' : 'is-offline' }}"></span>
          </div>

          <div class="setting-name">{{ $name }}</div>
          <div class="setting-role">{{ $role }}</div>

          <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
            @if($isApproved)
              <span class="badge-kk badge-green">
                <i class="fa fa-check-circle"></i> Approved
              </span>
            @else
              <span class="badge-kk" style="background:rgba(255,159,67,0.15); color:#ff9f43;">
                <i class="fa fa-clock"></i> Pending Approval
              </span>
            @endif

            @if($isOnline)
              <span class="badge-kk badge-green">
                <i class="fa fa-circle"></i> Online
              </span>
            @else
              <span class="badge-kk" style="background:rgba(108,117,125,0.15); color:#6c757d;">
                <i class="fa fa-circle"></i> Offline
              </span>
            @endif
          </div>

          @if($isApproved)
            <div class="mt-3">
              <button
                class="btn-kk btn-primary-kk online-toggle-btn"
                style="{{ $isOnline ? 'background:var(--danger); border-color:var(--danger);' : '' }}"
                wire:click="toggleOnlineStatus"
                wire:loading.attr="disabled"
                wire:target="toggleOnlineStatus"
              >
                <i class="fa fa-power-off"></i>
                {{ $isOnline ? 'Go Offline' : 'Go Online' }}
              </button>
            </div>
          @endif
        </div>

        {{-- Edit Form --}}
        <div class="setting-form">

          <div class="form-section-label">Contact Info</div>

          <div class="form-group">
            <label class="form-label-kk">Phone Number</label>
            <div class="input-icon-wrap">
              <i class="fa fa-phone input-icon"></i>
              <input class="form-control-kk has-icon" wire:model="phone" type="tel" placeholder="01XXXXXXXXX">
            </div>
            @error('phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label class="form-label-kk">Email</label>
            <div class="input-icon-wrap">
              <i class="fa fa-envelope input-icon"></i>
              <input class="form-control-kk has-icon" wire:model="email" type="email" placeholder="you@example.com">
            </div>
            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="form-section-label">Vehicle Info</div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label-kk">Vehicle Type</label>
              <select class="form-control-kk" wire:model="vehicleType">
                <option value="">Select</option>
                <option value="bike">Motorbike</option>
                <option value="bicycle">Bicycle</option>
                <option value="car">Car</option>
              </select>
              @error('vehicleType') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-6">
              <label class="form-label-kk">Vehicle Plate</label>
              <input class="form-control-kk" wire:model="vehiclePlate" type="text" placeholder="DHA-1234">
              @error('vehiclePlate') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="form-group">
            <label class="form-label-kk">License Number</label>
            <input class="form-control-kk" wire:model="licenseNumber" type="text" placeholder="Driving License No.">
            @error('licenseNumber') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label class="form-label-kk">NID Number</label>
            <input class="form-control-kk" wire:model="nidNumber" type="text" placeholder="National ID No.">
            @error('nidNumber') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="form-section-label">Coverage Area</div>

          <div class="form-group">
            <label class="form-label-kk">Zone</label>
            <input class="form-control-kk" wire:model="zone" type="text" placeholder="e.g. Dhaka-Metro">
            @error('zone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Current Location Map Picker --}}
          <div class="form-group mb-2">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <label class="form-label-kk mb-0">Current Location</label>
              <button
                type="button"
                id="useCurrentLocationBtnRider"
                class="btn-kk btn-ghost-kk btn-sm-kk"
              >
                <i class="fa fa-location-crosshairs"></i> Use my location
              </button>
            </div>

            <div class="map-frame">
              <div
                id="riderLocationMap"
                wire:ignore
              ></div>
            </div>

            <div class="location-hint">
              <i class="fa fa-map-marker-alt"></i>
              @if($currentLat && $currentLng)
                Selected: {{ number_format($currentLat, 6) }}, {{ number_format($currentLng, 6) }}
              @else
                Click on the map (or drag the marker) to set your current location.
              @endif
            </div>

            @error('currentLat') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            @error('currentLng') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <button
            class="btn-kk btn-primary-kk save-btn"
            wire:click="saveSetting"
            wire:loading.attr="disabled"
            wire:target="saveSetting"
          >
            <span wire:loading.remove wire:target="saveSetting">
              <i class="fa fa-save"></i> Save Changes
            </span>
            <span wire:loading wire:target="saveSetting">
              <i class="fa fa-spinner fa-spin"></i> Saving...
            </span>
          </button>

        </div>
      </div>
    </div>

    {{-- STATS + QUICK ACTIONS --}}
    <div class="col-md-6">
      <div class="d-flex flex-column gap-3">

        {{-- Stat Cards --}}
        <div class="stat-card sc-pink">
          <div class="stat-icon"><i class="fa fa-box"></i></div>
          <div class="stat-info">
            <div class="num">{{ $totalDeliveries }}</div>
            <div class="label">Total Deliveries</div>
          </div>
        </div>

        <div class="stat-card sc-green">
          <div class="stat-icon"><i class="fa fa-star"></i></div>
          <div class="stat-info">
            <div class="num">
              {{ $avgRating ? number_format($avgRating, 1) . '★' : 'N/A' }}
            </div>
            <div class="label">Avg. Rating</div>
          </div>
        </div>

        <div class="stat-card sc-orange">
          <div class="stat-icon"><i class="fa fa-map-marker-alt"></i></div>
          <div class="stat-info">
            <div class="num">{{ $zone ?: 'N/A' }}</div>
            <div class="label">Delivery Zone</div>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

@push('styles')
<style>
  :root {
    --pink: #e91e8c;
    --pink-dark: #c0167a;
    --pink-light: #ff4dab;
    --pink-soft: #fde8f4;
    --pink-mid: #f9c5e3;
    --accent: #ff6b35;
    --success: #16a34a;
    --danger: #ef4444;
    --border: #e5e7eb;
    --text-1: #1f2937;
    --text-2: #4b5563;
    --text-3: #9ca3af;
    --radius: 14px;
    --shadow: 0 2px 10px rgba(0,0,0,.06);
    --shadow-lg: 0 12px 30px rgba(233,30,140,.14);
  }

  .rider-setting .card {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    background: #fff;
    overflow: hidden;
  }

  /* ── Setting hero header ── */
  .setting-hero {
    position: relative;
    text-align: center;
    padding: 30px 20px 22px;
    overflow: hidden;
  }
  .setting-hero-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(160deg, var(--pink-soft) 0%, #fff 65%);
    z-index: 0;
  }
  .setting-hero > * { position: relative; z-index: 1; }


  .avatar-img,
  .avatar-fallback {
    width: 92px;
    height: 92px;
    border-radius: 50%;
    object-fit: cover;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-lg);
    border: 3px solid #fff;
  }
  .avatar-fallback {
    background: linear-gradient(135deg, var(--pink), var(--accent));
    color: #fff;
    font-size: 34px;
    font-weight: 800;
  }
  .avatar-status-dot {
    position: absolute;
    right: 3px;
    bottom: 3px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 3px solid #fff;
  }
  .avatar-status-dot.is-online  { background: var(--success); }
  .avatar-status-dot.is-offline { background: #9ca3af; }

  .setting-name {
    font-size: 21px;
    font-weight: 800;
    color: var(--text-1);
    letter-spacing: -.2px;
  }
  .setting-role {
    color: var(--text-3);
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-top: 2px;
  }

  .online-toggle-btn {
    padding: 10px 24px !important;
    border-radius: 999px !important;
    font-weight: 700 !important;
    box-shadow: 0 6px 16px rgba(233,30,140,.25);
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .online-toggle-btn:hover { transform: translateY(-1px); }

  /* ── Form ── */
  .setting-form {
    border-top: 1px solid var(--border);
    padding: 22px 22px 24px;
  }

  .form-section-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .7px;
    color: var(--pink);
    margin: 18px 0 10px;
  }
  .form-section-label:first-child { margin-top: 0; }

  .form-label-kk {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-2);
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: .4px;
  }

  .form-control-kk {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    font-size: 14px;
    color: var(--text-1);
    background: #fafafa;
    transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
  }
  .form-control-kk:focus {
    outline: none;
    border-color: var(--pink);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(233,30,140,.1);
  }

  .input-icon-wrap { position: relative; }
  .input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-3);
    font-size: 13px;
    pointer-events: none;
  }
  .form-control-kk.has-icon { padding-left: 38px; }

  .form-group { margin-bottom: 16px; }

  /* ── Buttons ── */
  .btn-kk {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 16px;
    border-radius: 10px;
    border: none;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .15s, transform .1s;
  }
  .btn-kk:hover  { opacity: .9; }
  .btn-kk:active { transform: scale(.97); }
  .btn-kk:disabled { opacity: .5; cursor: not-allowed; }

  .btn-primary-kk {
    background: linear-gradient(135deg, var(--pink), var(--pink-light));
    color: #fff;
  }
  .btn-ghost-kk {
    background: #f3f4f6;
    color: var(--text-2);
  }
  .btn-sm-kk { padding: 6px 12px; font-size: 12px; }

  .save-btn {
    width: 100%;
    justify-content: center;
    padding: 13px !important;
    font-size: 14px !important;
    border-radius: 12px !important;
    margin-top: 6px;
    box-shadow: 0 8px 20px rgba(233,30,140,.22);
  }

  /* ── Badges ── */
  .badge-kk {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 13px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 700;
  }
  .badge-green { background: #d1fae5; color: #065f46; }

  /* ── Map ── */
  .map-frame {
    border-radius: var(--radius);
    border: 1px solid var(--border);
    overflow: hidden;
    box-shadow: var(--shadow);
  }
  #riderLocationMap {
    height: 220px;
    width: 100%;
  }
  .location-hint {
    font-size: 12px;
    color: var(--text-2);
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .location-hint i { color: var(--pink); }

  /* ── Stat cards ── */
  .stat-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
    overflow: hidden;
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(0,0,0,.08);
  }
  .stat-card::after {
    content: "";
    position: absolute;
    right: -14px;
    bottom: -14px;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: currentColor;
    opacity: .06;
  }
  .stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
  }
  .stat-info .num {
    font-size: 25px;
    font-weight: 800;
    line-height: 1;
  }
  .stat-info .label {
    font-size: 12px;
    color: var(--text-3);
    margin-top: 6px;
    font-weight: 600;
  }

  .sc-pink   .stat-icon { background: var(--pink-soft); color: var(--pink); }
  .sc-pink   .num        { color: var(--pink); }
  .sc-green  .stat-icon { background: #d1fae5; color: var(--success); }
  .sc-green  .num        { color: var(--success); }
  .sc-orange .stat-icon { background: #fef3c7; color: #ca8a04; }
  .sc-orange .num        { color: #ca8a04; }
</style>
@endpush

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

    let riderMap    = null;
    let riderMarker = null;

    function placeRiderMarker(lat, lng) {
      if (!riderMap) return;

      if (riderMarker) {
        riderMarker.setLatLng([lat, lng]);
      } else {
        riderMarker = L.marker([lat, lng], { draggable: true }).addTo(riderMap);
        riderMarker.on('dragend', (e) => {
          const pos = e.target.getLatLng();
          @this.set('currentLat', pos.lat);
          @this.set('currentLng', pos.lng);
        });
      }
    }

    function initRiderMap(lat, lng) {
      const el = document.getElementById('riderLocationMap');
      if (!el) return;

      const centerLat = lat ?? DEFAULT_LAT;
      const centerLng = lng ?? DEFAULT_LNG;

      if (!riderMap) {
        riderMap = L.map('riderLocationMap').setView([centerLat, centerLng], lat ? 16 : 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap contributors',
        }).addTo(riderMap);

        riderMap.on('click', (e) => {
          placeRiderMarker(e.latlng.lat, e.latlng.lng);
          @this.set('currentLat', e.latlng.lat);
          @this.set('currentLng', e.latlng.lng);
        });
      } else {
        riderMap.setView([centerLat, centerLng], lat ? 16 : 12);
      }

      if (lat && lng) {
        placeRiderMarker(lat, lng);
      }

      setTimeout(() => riderMap.invalidateSize(), 200);
    }

    // Setting page loads without a modal — init once DOM is ready
    initRiderMap(@json($currentLat), @json($currentLng));

    // "Use my location" button
    document.getElementById('useCurrentLocationBtnRider')?.addEventListener('click', () => {
      if (!navigator.geolocation) {
        alert('Geolocation is not supported by this browser.');
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          const { latitude, longitude } = position.coords;
          placeRiderMarker(latitude, longitude);
          if (riderMap) riderMap.setView([latitude, longitude], 16);
          @this.set('currentLat', latitude);
          @this.set('currentLng', longitude);
        },
        () => alert('Unable to fetch your current location. Please allow location access.'),
      );
    });

  });
</script>
@endpush