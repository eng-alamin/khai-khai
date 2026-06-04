{{-- resources/views/livewire/customer/item-component.blade.php --}}
<div>

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
            class="cat-pill {{ $activeCategory === $cat->name ? 'active' : '' }}"
            wire:click="setCategory('{{ $cat->name }}')"
        >
            {{ $cat->emoji }} {{ $cat->name }}
        </div>
        @endforeach

    </div>

    {{-- Loading --}}
    <div wire:loading class="text-center py-3">
        <i class="fa fa-spinner fa-spin" style="color:var(--pink);"></i>
    </div>

    {{-- Food grid --}}
    <div class="row g-3" wire:loading.remove>
        @forelse($filteredItems as $item)
        <div class="col-md-3 col-6">
            <x-food-card :item="$item" />
        </div>
        @empty
        <div class="col-12">
            <div class="card text-center py-5">
                <i class="fa fa-utensils fa-3x mb-3" style="color: var(--text-3);"></i>
                <p style="color: var(--text-3);">এই ক্যাটাগরিতে কোনো আইটেম নেই।</p>
            </div>
        </div>
        @endforelse
    </div>

</div>