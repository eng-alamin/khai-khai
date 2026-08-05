{{-- resources/views/components/restaurant-card.blade.php --}}
@props(['restaurant'])

<div class="rest-card" style="cursor:pointer;" onclick="window.location='{{ route('customer.restaurant', $restaurant->slug) }}'">
    <div class="rest-thumb" style="position:relative; overflow:hidden;">
        @php
            $thumb = $restaurant->banner_url
                ?? $restaurant->logo_url
                ?? 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400&q=80';

            $rating = $restaurant->avg_rating
                ? number_format($restaurant->avg_rating, 1)
                : 'নতুন';
        @endphp

        <img
            src="{{ $thumb }}"
            class="rest-img"
            alt="{{ $restaurant->name }}"
            style="transition: transform 0.4s ease;"
            onmouseover="this.style.transform='scale(1.06)'"
            onmouseout="this.style.transform='scale(1)'"
        >

        {{-- Tag badge --}}
        @if(!empty($restaurant->tag))
            <span class="rest-tag">{{ $restaurant->tag }}</span>
        @endif

        {{-- Closed overlay --}}
        @if(!$restaurant->is_open)
            <div style="
                position:absolute; inset:0;
                background:rgba(0,0,0,0.45);
                display:flex; align-items:center; justify-content:center;
            ">
                <span style="
                    background:rgba(0,0,0,0.7); color:#fff;
                    font-size:12px; font-weight:700;
                    padding:4px 14px; border-radius:20px;
                ">এখন বন্ধ</span>
            </div>
        @endif
    </div>

    <div class="rest-info">
        <div class="rest-name">
            @if($restaurant->emoji) {{ $restaurant->emoji }} @endif
            {{ $restaurant->name }}
        </div>

        <div class="rest-meta" style="margin-bottom:6px;">
            <span style="color:var(--text-3); font-size:12px;">
                {{ $restaurant->category }}
            </span>
            @if($restaurant->city)
                <span style="color:var(--text-3); font-size:11px;">
                    · {{ $restaurant->city }}
                </span>
            @endif
        </div>

        <div style="display:flex; align-items:center; justify-content:flex-end;">
            <span class="rest-rating" style="font-size:13px;">
                ⭐ {{ $rating }}
                @if($restaurant->total_reviews > 0)
                    <span style="font-size:11px; color:var(--text-3);">
                        ({{ $restaurant->total_reviews }})
                    </span>
                @endif
            </span>
        </div>
    </div>
</div>