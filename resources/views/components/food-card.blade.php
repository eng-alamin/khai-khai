{{-- resources/views/components/food-card.blade.php --}}
@props(['item'])

@php
    $thumb = $item->image_url
        ?? asset('assets/images/default-menu-image-placeholder.png');

    $price = '৳' . number_format($item->price / 100);

    $catName  = $item->category?->name ?? '';
    $catEmoji = $item->category?->emoji ?? '';
    $restName = $item->restaurant?->name ?? '';
@endphp

<div class="food-card" style="position:relative; overflow:hidden;">
    <div style="position:relative; overflow:hidden;">
        <img
            src="{{ $thumb }}"
            class="food-img"
            alt="{{ $item->name }}"
            style="transition: transform 0.4s ease;"
            onerror="this.src='{{ asset('assetsimages/default-menu-image-placeholder.png') }}'"
            onmouseover="this.style.transform='scale(1.08)'"
            onmouseout="this.style.transform='scale(1)'"
        >

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
                {{ $restName }}
            </div>
        @endif

        <div class="food-name">
            @if($item->emoji) {{ $item->emoji }} @endif
            {{ $item->name }}
        </div>

        <div class="food-footer">
            <span class="food-price">{{ $price }}</span>
            <button
                class="food-add"
                title="কার্টে যোগ করুন"
                wire:click="$dispatch('add-to-cart', { id: {{ $item->id }}, name: '{{ addslashes($item->name) }}', price: {{ $item->price }} })"
                onclick="
                    this.innerHTML='<i class=\'fa fa-check\'></i>';
                    this.style.background='var(--success)';
                    setTimeout(() => {
                        this.innerHTML='<i class=\'fa fa-plus\'></i>';
                        this.style.background='var(--pink)';
                    }, 1200);
                "
            >
                <i class="fa fa-plus"></i>
            </button>
        </div>
    </div>
</div>