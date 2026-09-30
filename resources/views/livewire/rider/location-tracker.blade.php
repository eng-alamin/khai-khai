{{-- resources/views/livewire/rider/location-tracker.blade.php --}}
{{-- Styles: resources/css/blade.css (shared classes, Bootstrap 5 required) --}}
<div wire:ignore
     x-data="{
         watchId: null,
         init() {
             if (!navigator.geolocation) return;
             this.watchId = navigator.geolocation.watchPosition(
                 (pos) => this.send(pos),
                 (err) => console.warn('Geolocation error:', err.message),
                 { enableHighAccuracy: true, maximumAge: 5000 }
             );
             setInterval(() => this.pushCurrent(), 8000);
         },
         lastPos: null,
         send(pos) {
             this.lastPos = pos;
         },
         pushCurrent() {
             if (!this.lastPos) return;
             $wire.updateLocation(this.lastPos.coords.latitude, this.lastPos.coords.longitude);
         }
     }">
    <small class="text-muted">Location sharing active</small>
</div>
