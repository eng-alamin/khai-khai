{{-- resources/views/livewire/customer/restaurant-detail-component.blade.php --}}
<div>

    <a href="{{ route('customer.restaurants') }}" class="btn-kk btn-ghost-kk btn-sm-kk mb-3">
        <i class="fa fa-arrow-left"></i> সব রেস্তোরাঁ
    </a>

    @php
        $banner = $restaurant->banner_url
            ?? $restaurant->logo_url
            ?? 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200&q=80';
        $rating = $restaurant->avg_rating ? number_format($restaurant->avg_rating, 1) : 'নতুন';
    @endphp

    {{-- ===== Restaurant header ===== --}}
    <div class="card mb-4" style="padding:0; overflow:hidden;">
        <div style="position:relative; height:190px; background:var(--pink-ultra-soft);">
            <img src="{{ $banner }}" alt="{{ $restaurant->name }}"
                 style="width:100%; height:100%; object-fit:cover; display:block;">

            @if(! $restaurant->is_open)
                <div style="position:absolute; inset:0; background:rgba(0,0,0,.5); display:flex; align-items:center; justify-content:center;">
                    <span style="background:rgba(0,0,0,.75); color:#fff; font-size:14px; font-weight:700; padding:6px 18px; border-radius:20px;">
                        এখন বন্ধ
                    </span>
                </div>
            @endif

            @if(! empty($restaurant->tag))
                <span class="rest-tag">{{ $restaurant->tag }}</span>
            @endif
        </div>

        <div style="padding:16px 18px;">
            <h2 style="font-size:22px; font-weight:800; margin:0 0 6px;">
                @if($restaurant->emoji) {{ $restaurant->emoji }} @endif
                {{ $restaurant->name }}
            </h2>

            <div style="display:flex; flex-wrap:wrap; gap:6px 14px; font-size:13px; color:var(--text-3);">
                <span><i class="fa fa-utensils" style="color:var(--pink);"></i> {{ $restaurant->category }}</span>
                @if($restaurant->city)
                    <span><i class="fa fa-map-marker-alt" style="color:var(--pink);"></i> {{ $restaurant->city }}</span>
                @endif
                <span>
                    ⭐ {{ $rating }}
                    @if($restaurant->total_reviews > 0)
                        ({{ $restaurant->total_reviews }})
                    @endif
                </span>
                <span style="font-weight:700; color:{{ $restaurant->is_open ? 'var(--success, #16a34a)' : '#dc2626' }};">
                    ● {{ $restaurant->is_open ? 'খোলা আছে' : 'এখন বন্ধ' }}
                </span>
            </div>

            @if($restaurant->address)
                <div style="font-size:12px; color:var(--text-3); margin-top:8px;">
                    {{ $restaurant->address }}
                </div>
            @endif
        </div>
    </div>

    {{-- ===== Menu items ===== --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="card-title">🍽️ মেনু ({{ $totalItems }}টি আইটেম)</div>
    </div>

    @forelse($groups as $group)
        @php $items = $group['items']; @endphp
        <div style="font-weight:800; font-size:15px; margin:18px 0 10px; color:var(--text-1, inherit);">
            {{ $group['emoji'] }} {{ $group['title'] }}
            <span style="font-weight:600; font-size:12px; color:var(--text-3);">({{ $items->count() }})</span>
        </div>

        <div class="row g-3">
            @foreach($items as $item)
                <div class="col-md-3 col-6" wire:key="rd-item-{{ $item->id }}">
                    <x-food-card :item="$item" />
                </div>
            @endforeach
        </div>
    @empty
        <div class="card text-center py-5">
            <i class="fa fa-utensils fa-3x mb-3" style="color:var(--text-3);"></i>
            <p style="color:var(--text-3); margin:0;">এই রেস্তোরাঁয় এখন কোনো আইটেম নেই।</p>
        </div>
    @endforelse

</div>
