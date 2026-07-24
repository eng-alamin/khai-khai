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

<div>
  <div class="row g-3">

    {{-- PROFILE CARD --}}
    <div class="col-md-6">
      <div class="card">

        {{-- Avatar & Info --}}
        <div class="text-center py-4">
          @if(str_starts_with($avatar, 'http') || str_starts_with($avatar, '/'))
            <img
              src="{{ $avatar }}"
              style="width:80px; height:80px; border-radius:50%; object-fit:cover; margin:0 auto 12px; display:block; border:3px solid var(--pink);"
              alt="{{ $name }}"
            >
          @else
            <div style="width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,var(--pink),var(--accent)); display:flex; align-items:center; justify-content:center; font-size:32px; color:#fff; font-weight:700; margin:0 auto 12px;">
              {{ $avatar }}
            </div>
          @endif

          <div style="font-size:20px; font-weight:800;">{{ $name }}</div>
          <div style="color:var(--text-3); font-size:13px;">{{ $role }}</div>

          <div class="mt-3 d-flex justify-content-center gap-2">
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
                class="btn-kk btn-primary-kk"
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
        <div style="border-top:1px solid var(--border); padding-top:16px;">

          <div class="form-group">
            <label class="form-label-kk">Phone Number</label>
            <input class="form-control-kk" wire:model="phone" type="tel" placeholder="01XXXXXXXXX">
            @error('phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="form-group">
            <label class="form-label-kk">Email</label>
            <input class="form-control-kk" wire:model="email" type="email" placeholder="you@example.com">
            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

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

            <div
              id="riderLocationMap"
              wire:ignore
              style="height:220px; width:100%; border-radius:var(--radius); border:1px solid var(--border); overflow:hidden;"
            ></div>

            <div style="font-size:12px; color:var(--text-2); margin-top:6px;">
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
            class="btn-kk btn-primary-kk"
            style="width:100%; justify-content:center;"
            wire:click="saveProfile"
            wire:loading.attr="disabled"
            wire:target="saveProfile"
          >
            <span wire:loading.remove wire:target="saveProfile">
              <i class="fa fa-save"></i> Save Changes
            </span>
            <span wire:loading wire:target="saveProfile">
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

        {{-- Quick Actions --}}
        <div class="card">
          <div class="card-title mb-3">Quick Actions</div>
          <div class="d-flex flex-column gap-2">

            <button
              class="btn-kk btn-ghost-kk"
              style="justify-content:flex-start;"
              wire:click="changePassword"
            >
              <i class="fa fa-lock"></i> Change Password
            </button>

            <button
              class="btn-kk btn-ghost-kk"
              style="justify-content:flex-start;"
              wire:click="notifications"
            >
              <i class="fa fa-bell"></i> Notifications
            </button>

            <button
              class="btn-kk btn-ghost-kk"
              style="justify-content:flex-start; color:var(--danger);"
              wire:click="logout"
              wire:confirm="Are you sure you want to logout?"
            >
              <i class="fa fa-sign-out-alt"></i> Logout
            </button>

          </div>
        </div>

      </div>
    </div>

  </div>
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

    // Profile page loads without a modal — init once DOM is ready
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