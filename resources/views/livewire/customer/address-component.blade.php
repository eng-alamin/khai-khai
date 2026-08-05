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

  {{-- HEADER --}}
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div class="card-title">My Addresses</div>
    <button class="btn-kk btn-primary-kk btn-sm-kk" wire:click="openAddModal">
      <i class="fa fa-plus"></i> Add New Address
    </button>
  </div>

  {{-- ADDRESS LIST --}}
  <div class="d-flex flex-column gap-3">

    @forelse($addresses as $addr)
    @php
      $iconMap = [
        'home'   => 'fa-home',
        'office' => 'fa-briefcase',
        'other'  => 'fa-map-pin',
      ];
      $icon = $iconMap[$addr->label] ?? 'fa-map-marker-alt';
    @endphp

    <div class="card" wire:key="addr-{{ $addr->id }}">
      <div class="d-flex align-items-center justify-content-between gap-3">

        {{-- Icon + Info --}}
        <div class="d-flex align-items-center gap-3">
          <div style="width:40px; height:40px; border-radius:10px; background:var(--pink-soft); color:var(--pink); display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0;">
            <i class="fa {{ $icon }}"></i>
          </div>
          <div>
            <div style="font-weight:700;">
              {{ ucfirst($addr->label) }}
              @if($addr->is_default)
                <span class="badge-kk badge-pink ms-1">Default</span>
              @endif
            </div>
            <div style="font-size:13px; color:var(--text-2); margin-top:2px;">
              {{ $addr->full_address }}, {{ $addr->city }}
              @if($addr->postal_code) - {{ $addr->postal_code }} @endif
            </div>
            @if($addr->latitude && $addr->longitude)
            <div style="font-size:11px; color:var(--text-2); margin-top:2px;">
              <i class="fa fa-map-marker-alt"></i>
              {{ number_format($addr->latitude, 5) }}, {{ number_format($addr->longitude, 5) }}
            </div>
            @endif
          </div>
        </div>

        {{-- Action Buttons --}}
        <div class="d-flex gap-2 flex-shrink-0">
          @if(!$addr->is_default)
          <button
            class="btn-kk btn-ghost-kk btn-sm-kk"
            wire:click="setDefault({{ $addr->id }})"
            wire:loading.attr="disabled"
            wire:target="setDefault({{ $addr->id }})"
            title="Set as default"
          >
            <i class="fa fa-check-circle"></i>
          </button>
          @endif

          <button
            class="btn-kk btn-ghost-kk btn-sm-kk"
            wire:click="openEditModal({{ $addr->id }})"
            title="Edit"
          >
            <i class="fa fa-edit"></i>
          </button>

          <button
            class="btn-kk btn-ghost-kk btn-sm-kk"
            style="color:var(--danger);"
            wire:click="delete({{ $addr->id }})"
            wire:confirm="Are you sure you want to delete this address?"
            wire:loading.attr="disabled"
            wire:target="delete({{ $addr->id }})"
            title="Delete"
          >
            <i class="fa fa-trash"></i>
          </button>
        </div>

      </div>
    </div>

    @empty
    <div class="card text-center py-5">
      <div style="font-size:48px;" class="mb-3">📍</div>
      <div class="fw-bold fs-5 mb-2">No addresses saved</div>
      <div class="text-muted small mb-4">Add a delivery address to get started.</div>
      <div>
        <button class="btn-kk btn-primary-kk" wire:click="openAddModal">
          <i class="fa fa-plus"></i> Add Address
        </button>
      </div>
    </div>
    @endforelse

  </div>

  {{-- ADD / EDIT MODAL (Bootstrap 5) --}}
  <div
    class="modal fade"
    id="addressModal"
    tabindex="-1"
    aria-labelledby="addressModalLabel"
    aria-hidden="true"
    wire:ignore.self
  >
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius:var(--radius); border:1px solid var(--border); box-shadow:var(--shadow);">

        {{-- Header --}}
        <div class="modal-header" style="border-bottom:1px solid var(--border);">
          <h5 class="modal-title fw-bold" id="addressModalLabel">
            {{ $isEditing ? 'Edit Address' : 'Add New Address' }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        {{-- Body --}}
        <div class="modal-body p-4">

          {{-- Label Selector --}}
          <div class="form-group mb-3">
            <label class="form-label-kk mb-2">Address Type</label>
            <div class="d-flex gap-2">
              @foreach($labelOptions as $key => $opt)
              <button
                type="button"
                class="btn-kk btn-sm-kk {{ $label === $key ? 'btn-primary-kk' : 'btn-ghost-kk' }}"
                wire:click="$set('label', '{{ $key }}')"
              >
                <i class="fa {{ $opt['icon'] }}"></i> {{ $opt['text'] }}
              </button>
              @endforeach
            </div>
            @error('label') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Full Address --}}
          <div class="form-group mb-3">
            <label class="form-label-kk">Full Address</label>
            <textarea
              class="form-control mt-1"
              wire:model="fullAddress"
              rows="2"
              placeholder="House, Road, Area..."
              style="border-radius:var(--radius); border:1px solid var(--border); font-size:14px; resize:none;"
            ></textarea>
            @error('fullAddress') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- City + Postal --}}
          <div class="row g-2 mb-3">
            <div class="col-8">
              <label class="form-label-kk">City</label>
              <input class="form-control-kk mt-1" wire:model="city" type="text" placeholder="Dhaka">
              @error('city') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-4">
              <label class="form-label-kk">Postal Code</label>
              <input class="form-control-kk mt-1" wire:model="postalCode" type="text" placeholder="1200">
              @error('postalCode') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
          </div>

          {{-- Map Picker --}}
          <div class="form-group mb-2">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <label class="form-label-kk mb-0">Pin Location on Map</label>
              <button
                type="button"
                id="useCurrentLocationBtn"
                class="btn-kk btn-ghost-kk btn-sm-kk"
              >
                <i class="fa fa-location-crosshairs"></i> Use my location
              </button>
            </div>

            <div
              id="addressMap"
              wire:ignore
              style="height:220px; width:100%; border-radius:var(--radius); border:1px solid var(--border); overflow:hidden;"
            ></div>

            <div style="font-size:12px; color:var(--text-2); margin-top:6px;">
              <i class="fa fa-map-marker-alt"></i>
              @if($latitude && $longitude)
                Selected: {{ number_format($latitude, 6) }}, {{ number_format($longitude, 6) }}
              @else
                Click on the map (or drag the marker) to select the exact delivery point.
              @endif
            </div>

            @error('latitude') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            @error('longitude') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
          </div>

        </div>

        {{-- Footer --}}
        <div class="modal-footer" style="border-top:1px solid var(--border);">
          <button type="button" class="btn-kk btn-ghost-kk" data-bs-dismiss="modal">
            Cancel
          </button>
          <button
            type="button"
            class="btn-kk btn-primary-kk"
            wire:click="save"
            wire:loading.attr="disabled"
            wire:target="save"
          >
            <span wire:loading.remove wire:target="save">
              <i class="fa fa-save"></i> {{ $isEditing ? 'Update' : 'Save' }}
            </span>
            <span wire:loading wire:target="save">
              <i class="fa fa-spinner fa-spin"></i> Saving...
            </span>
          </button>
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

    let addressMap   = null;
    let addressMarker = null;

    function placeMarker(lat, lng) {
      if (!addressMap) return;

      if (addressMarker) {
        addressMarker.setLatLng([lat, lng]);
      } else {
        addressMarker = L.marker([lat, lng], { draggable: true }).addTo(addressMap);
        addressMarker.on('dragend', (e) => {
          const pos = e.target.getLatLng();
          @this.set('latitude', pos.lat);
          @this.set('longitude', pos.lng);
        });
      }
    }

    function initMap(lat, lng) {
      const centerLat = lat ?? DEFAULT_LAT;
      const centerLng = lng ?? DEFAULT_LNG;

      if (!addressMap) {
        addressMap = L.map('addressMap').setView([centerLat, centerLng], lat ? 16 : 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap contributors',
        }).addTo(addressMap);

        addressMap.on('click', (e) => {
          placeMarker(e.latlng.lat, e.latlng.lng);
          @this.set('latitude', e.latlng.lat);
          @this.set('longitude', e.latlng.lng);
        });
      } else {
        addressMap.setView([centerLat, centerLng], lat ? 16 : 12);
      }

      if (lat && lng) {
        placeMarker(lat, lng);
      } else if (addressMarker) {
        addressMap.removeLayer(addressMarker);
        addressMarker = null;
      }

      // Map container was hidden (inside a modal) so Leaflet needs a nudge
      // to recalculate its size once it becomes visible.
      setTimeout(() => addressMap.invalidateSize(), 200);
    }

    // Livewire event দিয়ে modal open + map init
    Livewire.on('open-address-modal', (payload) => {
      const data = Array.isArray(payload) ? payload[0] : payload;
      const el = document.getElementById('addressModal');
      if (el) bootstrap.Modal.getOrCreateInstance(el).show();

      // Wait for the modal transition so the container has real dimensions
      setTimeout(() => initMap(data?.latitude ?? null, data?.longitude ?? null), 150);
    });

    // Livewire event দিয়ে modal close
    Livewire.on('close-address-modal', () => {
      const el = document.getElementById('addressModal');
      if (el) bootstrap.Modal.getOrCreateInstance(el).hide();
    });

    // FIX: use event delegation on `document` instead of capturing
    // #addressModal / #useCurrentLocationBtn once via getElementById() inside
    // livewire:init. That element may not exist yet at init time (Livewire
    // mounts async), and any later Livewire re-render can replace the node,
    // silently detaching the old listener. Delegation always finds the
    // current element, filtered by event.target/its closest ancestor id.
    document.addEventListener('hidden.bs.modal', (event) => {
      if (event.target.id === 'addressModal') {
        @this.set('showModal', false);
      }
    });

    document.addEventListener('click', (event) => {
      const btn = event.target.closest('#useCurrentLocationBtn');
      if (!btn) return;

      if (!navigator.geolocation) {
        alert('Geolocation is not supported by this browser.');
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          const { latitude, longitude } = position.coords;
          placeMarker(latitude, longitude);
          if (addressMap) addressMap.setView([latitude, longitude], 16);
          @this.set('latitude', latitude);
          @this.set('longitude', longitude);
        },
        () => alert('Unable to fetch your current location. Please allow location access.'),
      );
    });

  });
</script>
@endpush