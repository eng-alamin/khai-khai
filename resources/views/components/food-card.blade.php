{{-- resources/views/components/food-card.blade.php --}}
@props(['item'])

@php
    $thumb    = $item->image_url
        ?? asset('assets/images/default-menu-image-placeholder.png');
    $price    = '৳' . number_format($item->price);
    $catName  = $item->category?->name  ?? '';
    $catEmoji = $item->category?->emoji ?? '';
    $restName = $item->restaurant?->name ?? '';
    $restSlug = $item->restaurant?->slug ?? null;
    $detailUrl = route('customer.food', $item->id);
@endphp

<div class="food-card" style="position:relative; overflow:hidden;">

    <div style="position:relative; overflow:hidden;">
      <a href="{{ $detailUrl }}" style="display:block; text-decoration:none; color:inherit;" aria-label="{{ $item->name }}">
        <img
            src="{{ $thumb }}"
            class="food-img"
            alt="{{ $item->name }}"
            style="transition: transform 0.4s ease;"
            onerror="this.src='{{ asset('assets/images/default-menu-image-placeholder.png') }}'"
            onmouseover="this.style.transform='scale(1.08)'"
            onmouseout="this.style.transform='scale(1)'"
        >
      </a>

        @if($catName)
            <span style="
                position:absolute; bottom:8px; left:8px;
                background:rgba(30,27,75,0.75);
                color:#fff; font-size:10px; font-weight:700;
                padding:2px 8px; border-radius:20px;
                backdrop-filter:blur(4px);
            ">{{ $catEmoji }} {{ $catName }}</span>
        @endif
    </div>

    <div class="food-body">
        @if($restName)
            <div class="food-rest">
                <i class="fa fa-store" style="font-size:10px; color:var(--pink);"></i>
                @if($restSlug)
                    <a href="{{ route('customer.restaurant', $restSlug) }}"
                       style="color:inherit; text-decoration:none;">{{ $restName }}</a>
                @else
                    {{ $restName }}
                @endif
            </div>
        @endif

        <div class="food-name">
            <a href="{{ $detailUrl }}" style="color:inherit; text-decoration:none;">
                @if($item->emoji) {{ $item->emoji }} @endif
                {{ $item->name }}
            </a>
        </div>

        <div class="food-footer">
            <span class="food-price">{{ $price }}</span>

            {{--
                ✅ FIX: wire:click দিয়ে Livewire server event dispatch করো।
                food-card একটা Blade component — Livewire না।
                তাই $dispatch() দিয়ে browser event না পাঠিয়ে
                Livewire.dispatch() দিয়ে server-side CartComponent-কে জানাও।
            --}}
            <button
                class="food-add"
                title="কার্টে যোগ করুন"
                onclick="
                    Livewire.dispatch('add-to-cart', { id: {{ $item->id }} });
                    this.innerHTML = '<i class=\'fa fa-check\'></i>';
                    this.style.background = 'var(--success)';
                    const btn = this;
                    setTimeout(() => {
                        btn.innerHTML = '<i class=\'fa fa-plus\'></i>';
                        btn.style.background = 'var(--pink)';
                    }, 1200);
                "
            >
                <i class="fa fa-plus"></i>
            </button>
        </div>
    </div>
</div>