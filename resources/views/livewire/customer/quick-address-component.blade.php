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

  {{-- QUICK ADD ADDRESS MODAL (Bootstrap 5) --}}
  <div
    class="modal fade"
    id="quickAddressModal"
    tabindex="-1"
    aria-labelledby="quickAddressModalLabel"
    aria-hidden="true"
    data-bs-backdrop="static"
    wire:ignore.self
  >
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius:var(--radius); border:1px solid var(--border); box-shadow:var(--shadow);">

        {{-- Header --}}
        <div class="modal-header" style="border-bottom:1px solid var(--border);">
          <div>
            <h5 class="modal-title fw-bold mb-0" id="quickAddressModalLabel">
              ডেলিভারি ঠিকানা যোগ করুন
            </h5>
            <div style="font-size:12px; color:var(--text-2); margin-top:2px;">
              অর্ডার করার আগে অন্তত একটি ঠিকানা প্রয়োজন
            </div>
          </div>
          <button type="button" class="btn-close" wire:click="cancel"></button>
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
                id="quickUseCurrentLocationBtn"
                class="btn-kk btn-ghost-kk btn-sm-kk"
              >
                <i class="fa fa-location-crosshairs"></i> Use my location
              </button>
            </div>

            <div
              id="quickAddressMap"
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
          <button type="button" class="btn-kk btn-ghost-kk" wire:click="cancel">
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
              <i class="fa fa-save"></i> Save &amp; Continue
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

    const QA_DEFAULT_LAT = 23.8103; // Dhaka
    const QA_DEFAULT_LNG = 90.4125;

    let quickMap    = null;
    let quickMarker = null;

    function quickPlaceMarker(lat, lng) {
      if (!quickMap) return;

      if (quickMarker) {
        quickMarker.setLatLng([lat, lng]);
      } else {
        quickMarker = L.marker([lat, lng], { draggable: true }).addTo(quickMap);
        quickMarker.on('dragend', (e) => {
          const pos = e.target.getLatLng();
          @this.set('latitude', pos.lat);
          @this.set('longitude', pos.lng);
        });
      }
    }

    function quickInitMap() {
      if (!quickMap) {
        quickMap = L.map('quickAddressMap').setView([QA_DEFAULT_LAT, QA_DEFAULT_LNG], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap contributors',
        }).addTo(quickMap);

        quickMap.on('click', (e) => {
          quickPlaceMarker(e.latlng.lat, e.latlng.lng);
          @this.set('latitude', e.latlng.lat);
          @this.set('longitude', e.latlng.lng);
        });
      } else {
        quickMap.setView([QA_DEFAULT_LAT, QA_DEFAULT_LNG], 12);
        if (quickMarker) {
          quickMap.removeLayer(quickMarker);
          quickMarker = null;
        }
      }

      // Map container was hidden (inside a modal) so Leaflet needs a nudge
      // to recalculate its size once it becomes visible.
      setTimeout(() => quickMap.invalidateSize(), 200);
    }

    // Open modal + init map
    Livewire.on('open-quick-address-modal', () => {
      const el = document.getElementById('quickAddressModal');
      if (el) bootstrap.Modal.getOrCreateInstance(el).show();
      setTimeout(() => quickInitMap(), 150);
    });

    // Close modal
    Livewire.on('close-quick-address-modal', () => {
      const el = document.getElementById('quickAddressModal');
      if (el) bootstrap.Modal.getOrCreateInstance(el).hide();
    });

    // FIX: event delegation on `document` instead of a one-time getElementById()
    // capture inside livewire:init — see address-component.blade.php for the
    // full explanation of the mount-timing root cause.
    document.addEventListener('hidden.bs.modal', (event) => {
      if (event.target.id === 'quickAddressModal') {
        @this.set('showModal', false);
      }
    });

    document.addEventListener('click', (event) => {
      const btn = event.target.closest('#quickUseCurrentLocationBtn');
      if (!btn) return;

      if (!navigator.geolocation) {
        alert('Geolocation is not supported by this browser.');
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          const { latitude, longitude } = position.coords;
          quickPlaceMarker(latitude, longitude);
          if (quickMap) quickMap.setView([latitude, longitude], 16);
          @this.set('latitude', latitude);
          @this.set('longitude', longitude);
        },
        () => alert('Unable to fetch your current location. Please allow location access.'),
      );
    });

  });
</script>
@endpush