{{-- resources/views/livewire/customer/order-tracking-map.blade.php --}}
<div wire:poll.8s>
    <div id="order-tracking-map" style="height: 400px; border-radius: var(--radius-lg);" wire:ignore></div>

    <div id="map-data"
         data-restaurant-lat="{{ $restaurant->latitude }}"
         data-restaurant-lng="{{ $restaurant->longitude }}"
         data-dest-lat="{{ $destLat }}"
         data-dest-lng="{{ $destLng }}"
         data-rider-lat="{{ $this->riderLocation['lat'] ?? '' }}"
         data-rider-lng="{{ $this->riderLocation['lng'] ?? '' }}"
         class="d-none"></div>

    @if (!$restaurant?->latitude || !$restaurant?->longitude)
        <small class="text-danger d-block mt-1">
            ⚠️ Restaurant location সেট নেই — ডিফল্ট Dhaka center দেখানো হচ্ছে।
        </small>
    @endif

    @if ($this->riderLocation)
        <small class="text-muted">Rider location updated {{ $this->riderLocation['updated_at'] }}</small>
    @endif
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<style>
    .kk-map-pin {
        display: flex; align-items: center; justify-content: center;
        width: 34px; height: 34px; border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        box-shadow: 0 2px 6px rgba(0,0,0,.35);
        border: 2px solid #fff;
    }
    .kk-map-pin span {
        transform: rotate(45deg);
        font-size: 16px; line-height: 1;
    }
    .kk-pin-restaurant { background: #f97316; } /* কমলা — Restaurant */
    .kk-pin-destination { background: #2563eb; } /* নীল — Delivery Address */
    .kk-pin-rider {
        background: transparent; box-shadow: none; border: none;
        transform: none; display: flex; align-items: center; justify-content: center;
    }
    .kk-pin-rider span { transform: none; font-size: 24px; }
</style>
<script>
document.addEventListener('livewire:init', () => {
    let map, restaurantMarker, destMarker, riderMarker;

    const restaurantIcon = L.divIcon({
        className: '',
        html: '<div class="kk-map-pin kk-pin-restaurant"><span>🏪</span></div>',
        iconSize: [34, 34],
        iconAnchor: [17, 34],
        popupAnchor: [0, -34],
    });

    const destinationIcon = L.divIcon({
        className: '',
        html: '<div class="kk-map-pin kk-pin-destination"><span>📍</span></div>',
        iconSize: [34, 34],
        iconAnchor: [17, 34],
        popupAnchor: [0, -34],
    });

    const riderIcon = L.divIcon({
        className: '',
        html: '<div class="kk-pin-rider"><span>🛵</span></div>',
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -14],
    });

    function readData() {
        const el = document.getElementById('map-data');
        return {
            rLat: parseFloat(el.dataset.restaurantLat),
            rLng: parseFloat(el.dataset.restaurantLng),
            dLat: parseFloat(el.dataset.destLat),
            dLng: parseFloat(el.dataset.destLng),
            riderLat: el.dataset.riderLat ? parseFloat(el.dataset.riderLat) : null,
            riderLng: el.dataset.riderLng ? parseFloat(el.dataset.riderLng) : null,
        };
    }

    function initMap() {
        const d = readData();

        // Restaurant lat/lng missing হলে Dhaka-এর কেন্দ্র fallback হিসেবে ব্যবহার করে crash এড়ানো হচ্ছে
        const centerLat = !isNaN(d.rLat) ? d.rLat : 23.8103;
        const centerLng = !isNaN(d.rLng) ? d.rLng : 90.4125;

        map = L.map('order-tracking-map').setView([centerLat, centerLng], 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        if (!isNaN(d.rLat) && !isNaN(d.rLng)) {
            restaurantMarker = L.marker([d.rLat, d.rLng], { icon: restaurantIcon }).addTo(map).bindPopup('🏪 Restaurant');
        }
        if (!isNaN(d.dLat) && !isNaN(d.dLng)) {
            destMarker = L.marker([d.dLat, d.dLng], { icon: destinationIcon }).addTo(map).bindPopup('📍 Delivery Address');
        }
        updateRider(d);
        fitBounds();

        setTimeout(() => map.invalidateSize(), 100);
    }

    function updateRider(d) {
        if (d.riderLat === null || isNaN(d.riderLat)) {
            if (riderMarker) { map.removeLayer(riderMarker); riderMarker = null; }
            return;
        }
        if (!riderMarker) {
            riderMarker = L.marker([d.riderLat, d.riderLng], { icon: riderIcon }).addTo(map).bindPopup('🛵 Rider');
        } else {
            riderMarker.setLatLng([d.riderLat, d.riderLng]);
        }
        fitBounds();
    }

    function fitBounds() {
        const points = [];
        if (restaurantMarker) points.push(restaurantMarker.getLatLng());
        if (destMarker) points.push(destMarker.getLatLng());
        if (riderMarker) points.push(riderMarker.getLatLng());
        if (points.length > 1) map.fitBounds(L.latLngBounds(points), { padding: [40, 40] });
    }

    initMap();

    Livewire.hook('morph.updated', ({ el }) => {
        if (el.id === 'map-data') updateRider(readData());
    });
});
</script>
@endpush