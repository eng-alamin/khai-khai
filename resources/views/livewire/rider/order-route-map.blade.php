{{-- resources/views/livewire/rider/order-route-map.blade.php --}}
<div wire:poll.8s>
    <div id="rider-route-map" style="height: 400px; width: 100%; border-radius: var(--radius-lg);" wire:ignore></div>

    <div id="rider-map-data"
         data-restaurant-lat="{{ $restaurant->latitude }}"
         data-restaurant-lng="{{ $restaurant->longitude }}"
         data-dest-lat="{{ $destLat }}"
         data-dest-lng="{{ $destLng }}"
         data-my-lat="{{ $this->myLocation['lat'] ?? '' }}"
         data-my-lng="{{ $this->myLocation['lng'] ?? '' }}"
         class="d-none"></div>

    @if (!$restaurant?->latitude || !$restaurant?->longitude)
        <small class="text-danger d-block mt-1">
            ⚠️ Restaurant location সেট নেই — ডিফল্ট Dhaka center দেখানো হচ্ছে।
        </small>
    @endif

    @if ($this->myLocation)
        <small class="text-muted">Your location updated {{ $this->myLocation['updated_at'] }}</small>
    @else
        <small class="text-muted">আপনার লোকেশন এখনো পাওয়া যায়নি — location permission চালু আছে কিনা দেখুন।</small>
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
    let map, restaurantMarker, destMarker, myMarker;

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

    const myIcon = L.divIcon({
        className: '',
        html: '<div class="kk-pin-rider"><span>🛵</span></div>',
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -14],
    });

    function readData() {
        const el = document.getElementById('rider-map-data');
        return {
            rLat: parseFloat(el.dataset.restaurantLat),
            rLng: parseFloat(el.dataset.restaurantLng),
            dLat: parseFloat(el.dataset.destLat),
            dLng: parseFloat(el.dataset.destLng),
            myLat: el.dataset.myLat ? parseFloat(el.dataset.myLat) : null,
            myLng: el.dataset.myLng ? parseFloat(el.dataset.myLng) : null,
        };
    }

    function initMap() {
        const d = readData();

        // Restaurant lat/lng missing হলে Dhaka-এর কেন্দ্র fallback হিসেবে ব্যবহার করে crash এড়ানো হচ্ছে
        const centerLat = !isNaN(d.rLat) ? d.rLat : (!isNaN(d.myLat) ? d.myLat : 23.8103);
        const centerLng = !isNaN(d.rLng) ? d.rLng : (!isNaN(d.myLng) ? d.myLng : 90.4125);

        map = L.map('rider-route-map').setView([centerLat, centerLng], 13);
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
        updateMyLocation(d);
        fitBounds();
    }

    function updateMyLocation(d) {
        if (d.myLat === null || isNaN(d.myLat)) {
            if (myMarker) { map.removeLayer(myMarker); myMarker = null; }
            return;
        }
        if (!myMarker) {
            myMarker = L.marker([d.myLat, d.myLng], { icon: myIcon }).addTo(map).bindPopup('🛵 You');
        } else {
            myMarker.setLatLng([d.myLat, d.myLng]);
        }
        fitBounds();
    }

    function fitBounds() {
        const points = [];
        if (restaurantMarker) points.push(restaurantMarker.getLatLng());
        if (destMarker) points.push(destMarker.getLatLng());
        if (myMarker) points.push(myMarker.getLatLng());
        if (points.length > 1) map.fitBounds(L.latLngBounds(points), { padding: [30, 30] });
    }

    initMap();

    // Alpine-এর x-show div সরাসরি নিজের style বদলায় (display:none ↔ block),
    // কিন্তু map div-এর ঠিক parent (wire:poll wrapper) সেটা না — তাই MutationObserver
    // দিয়ে সঠিক element ধরা কঠিন/ভঙ্গুর। এর বদলে blade থেকে dispatch করা
    // 'kk-map-shown' custom event শুনে নির্ভরযোগ্যভাবে invalidateSize() কল করা হচ্ছে।
    window.addEventListener('kk-map-shown', (e) => {
        setTimeout(() => map.invalidateSize(), 50);
    });

    // প্রথমবার visible থাকলেও (edge case) নিশ্চিত করতে একবার কল করা হচ্ছে
    setTimeout(() => map.invalidateSize(), 100);

    Livewire.hook('morph.updated', ({ el }) => {
        if (el.id === 'rider-map-data') updateMyLocation(readData());
    });
});
</script>
@endpush