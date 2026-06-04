{{-- resources/views/livewire/customer/restaurant-component.blade.php --}}
<div>

    {{-- Location alert --}}
    <div class="alert-kk alert-pink-kk mb-4">
        <i class="fa fa-map-marker-alt"></i>
        আপনার এলাকা: <strong>Dhaka</strong>
        — {{ $filteredRestaurants->count() }}টি রেস্তোরাঁ পাওয়া গেছে
    </div>

    {{-- Category pills --}}
    <div class="cat-pills mb-4">

        <div
            class="cat-pill {{ $activeCategory === null ? 'active' : '' }}"
            wire:click="setCategory(null)"
        >
            🍽️ সব
        </div>

        @foreach($categories as $cat)
        <div
            class="cat-pill {{ $activeCategory === $cat ? 'active' : '' }}"
            wire:click="setCategory('{{ $cat }}')"
        >
            {{ $cat }}
        </div>
        @endforeach

    </div>

    {{-- Loading --}}
    <div wire:loading class="text-center py-3">
        <i class="fa fa-spinner fa-spin" style="color:var(--pink);"></i>
        লোড হচ্ছে...
    </div>

    {{-- Restaurant grid --}}
    <div class="row g-3" wire:loading.remove>
        @forelse($filteredRestaurants as $restaurant)
        <div class="col-md-4 col-sm-6">
            <x-restaurant-card :restaurant="$restaurant" />
        </div>
        @empty
        <div class="col-12">
            <div class="card text-center py-5">
                <i class="fa fa-store fa-3x mb-3" style="color:var(--text-3);"></i>
                <p style="color:var(--text-3);">এই ক্যাটাগরিতে কোনো রেস্তোরাঁ নেই।</p>
            </div>
        </div>
        @endforelse
    </div>

</div>