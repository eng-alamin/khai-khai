{{-- resources/views/livewire/customer/food-detail-component.blade.php --}}
<div>

    <a href="{{ route('customer.restaurant', $food->restaurant->slug) }}" class="btn-kk btn-ghost-kk btn-sm-kk mb-3">
        <i class="fa fa-arrow-left"></i> {{ $food->restaurant->name }}-এ ফিরে যান
    </a>

    @php
        $placeholder = asset('assets/images/default-menu-image-placeholder.png');
        $thumb       = $food->image_url ?: $placeholder;
        $canOrder    = $food->is_available && $food->restaurant->is_open;
        $hasDiscount = $food->compare_price && $food->compare_price > $food->price;
    @endphp

    <div class="card">
        <div class="row g-4 align-items-center">

            {{-- Image --}}
            <div class="col-md-5">
                <div style="border-radius:16px; overflow:hidden; background:var(--pink-ultra-soft); aspect-ratio:1/1; display:flex; align-items:center; justify-content:center;">
                    <img src="{{ $thumb }}"
                         alt="{{ $food->name }}"
                         style="width:100%; height:100%; object-fit:cover;"
                         onerror="this.onerror=null; this.src='{{ $placeholder }}'">
                </div>
            </div>

            {{-- Info --}}
            <div class="col-md-7">

                @if($food->category)
                    <span class="badge-pink" style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; margin-bottom:8px;">
                        {{ $food->category->emoji }} {{ $food->category->name }}
                    </span>
                @endif

                <h2 style="font-size:24px; font-weight:800; margin-bottom:6px;">
                    @if($food->emoji) {{ $food->emoji }} @endif
                    {{ $food->name }}
                </h2>

                {{-- Restaurant link --}}
                <a href="{{ route('customer.restaurant', $food->restaurant->slug) }}"
                   style="display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--text-3); text-decoration:none; margin-bottom:12px;">
                    <i class="fa fa-store" style="color:var(--pink);"></i>
                    <strong>{{ $food->restaurant->name }}</strong>
                    @if($food->restaurant->city) · {{ $food->restaurant->city }} @endif
                    @if($food->restaurant->avg_rating) · ⭐ {{ number_format($food->restaurant->avg_rating, 1) }} @endif
                </a>

                {{-- Price --}}
                <div style="display:flex; align-items:baseline; gap:10px; margin-bottom:14px; flex-wrap:wrap;">
                    <span style="font-size:28px; font-weight:800; color:var(--pink); font-family:'Nunito',sans-serif;">
                        ৳{{ number_format($food->price, 0) }}
                    </span>
                    @if($hasDiscount)
                        <span style="text-decoration:line-through; color:var(--text-3);">
                            ৳{{ number_format($food->compare_price, 0) }}
                        </span>
                        <span class="badge-pink" style="padding:2px 8px; border-radius:20px; font-size:12px; font-weight:700;">
                            -{{ round((($food->compare_price - $food->price) / $food->compare_price) * 100) }}%
                        </span>
                    @endif
                </div>

                @if($food->description)
                    <p style="font-size:14px; color:var(--text-2); line-height:1.7; margin-bottom:18px;">
                        {{ $food->description }}
                    </p>
                @endif

                {{-- Status / Add to cart --}}
                @if(! $food->is_available)
                    <div style="color:#dc2626; font-weight:700; font-size:14px; margin-bottom:10px;">
                        এই আইটেমটি এখন পাওয়া যাচ্ছে না।
                    </div>
                @elseif(! $food->restaurant->is_open)
                    <div style="color:#dc2626; font-weight:700; font-size:14px; margin-bottom:10px;">
                        রেস্তোরাঁটি এখন বন্ধ, পরে অর্ডার করুন।
                    </div>
                @endif

                <button type="button"
                        class="btn-kk btn-primary-kk"
                        style="font-size:15px; padding:10px 22px; {{ $canOrder ? '' : 'opacity:.5; cursor:not-allowed;' }}"
                        @disabled(! $canOrder)
                        onclick="
                            Livewire.dispatch('add-to-cart', { id: {{ $food->id }} });
                            const b = this, old = b.innerHTML;
                            b.innerHTML = '<i class=\'fa fa-check\'></i> কার্টে যোগ হয়েছে';
                            setTimeout(() => b.innerHTML = old, 1400);
                        ">
                    <i class="fa fa-plus"></i> Add to cart
                </button>

            </div>
        </div>
    </div>

    {{-- ===== Ei restaurant-er aro item ===== --}}
    @if($moreItems->isNotEmpty())
        <div class="d-flex align-items-center justify-content-between mt-4 mb-3">
            <div class="card-title">🍴 {{ $food->restaurant->name }}-এর আরও আইটেম</div>
            <a href="{{ route('customer.restaurant', $food->restaurant->slug) }}" class="btn-kk btn-ghost-kk btn-sm-kk">
                সব দেখুন <i class="fa fa-arrow-right"></i>
            </a>
        </div>
        <div class="row g-3">
            @foreach($moreItems as $item)
                <div class="col-md-3 col-6" wire:key="more-{{ $item->id }}">
                    <x-food-card :item="$item" />
                </div>
            @endforeach
        </div>
    @endif

</div>
