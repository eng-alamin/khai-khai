{{-- resources/views/livewire/customer/customer-home.blade.php --}}
<div>

    {{-- ===== HERO SECTION: Location + Search + Slider ===== --}}
    <div class="kk-hero">

        {{-- Location bar --}}
        <div class="kk-hero-location">
            <div class="kk-hero-location-left">
                <i class="fa fa-map-marker-alt kk-hero-pin"></i>
                <div class="kk-hero-location-text">
                    <div class="kk-hero-location-label">
                        {{ $currentAddress->label ?? 'ঠিকানা যোগ করুন' }}
                    </div>
                    @if($currentAddress)
                        <div class="kk-hero-location-sub">
                            {{ \Illuminate\Support\Str::limit($currentAddress->full_address, 40) }}
                        </div>
                    @else
                        <div class="kk-hero-location-sub">ডেলিভারি লোকেশন সেট করুন</div>
                    @endif
                </div>
            </div>
            <a href="{{ route('customer.addresses') }}" class="kk-hero-heart" aria-label="Saved addresses">
                <i class="fa fa-heart"></i>
            </a>
        </div>

        {{-- Search bar --}}
        <div class="kk-hero-search">
            <i class="fa fa-search kk-hero-search-icon"></i>
            <input
                wire:model="searchQuery"
                wire:keydown.enter="searchFood"
                placeholder="খাবার বা রেস্টুরেন্ট খুঁজুন...">
            <button class="kk-hero-search-btn" wire:click="searchFood">খুঁজুন</button>
        </div>

        {{-- Promo slider --}}
        @if($sliders->isNotEmpty())
            <div class="kk-hero-slider" id="kkHeroSlider">
                @foreach($sliders as $slider)
                    @if($slider->url)
                        <a href="{{ $slider->url }}" class="kk-hero-slide" target="_blank" rel="noopener">
                            <img src="{{ $slider->image }}" alt="Promo slide">
                        </a>
                    @else
                        <div class="kk-hero-slide">
                            <img src="{{ $slider->image }}" alt="Promo slide">
                        </div>
                    @endif
                @endforeach
            </div>
            @if($sliders->count() > 1)
                <div class="kk-hero-slider-dots">
                    @foreach($sliders as $i => $slider)
                        <span class="kk-hero-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                    @endforeach
                </div>
            @endif
        @endif

    </div>

    {{-- ===== FEATURED PRODUCTS ===== --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="card-title">🛍️ Featured Products</div>
    </div>
    <div class="row g-3 mb-4">
        @php $colors = ['sc-pink', 'sc-green', 'sc-orange', 'sc-blue']; @endphp
        @forelse($products as $index => $product)
            <div class="col-md-3 col-12">
                <div class="stat-card {{ $colors[$index % 4] }} product-card">
                    <div class="stat-icon product-icon">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                        @else
                            <i class="fa fa-shopping-bag"></i>
                        @endif
                    </div>
                    <div class="stat-info">
                        <div class="num">৳{{ number_format($product->price, 0) }}</div>
                        <div class="label">{{ $product->name }}</div>
                        @if($product->compare_price && $product->compare_price > $product->price)
                            <div class="change up">
                                <i class="fa fa-arrow-down"></i>
                                -{{ round((($product->compare_price - $product->price) / $product->compare_price) * 100) }}%
                                <span class="old-price">৳{{ number_format($product->compare_price, 0) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted py-4">No products available right now.</div>
        @endforelse
    </div>

    {{-- ===== CATEGORY PILLS ===== --}}
    <div class="cat-pills">
        <div class="cat-pill active" wire:click="filterByCategory()">
            <span class="emoji">🍽️</span> All
        </div>
        @foreach($categories as $category)
            <div class="cat-pill" wire:click="filterByCategory({{ $category->id }})">
                <span class="emoji">{{ $category->emoji }}</span> {{ $category->name }}
            </div>
        @endforeach
    </div>

    {{-- ===== POPULAR RESTAURANTS ===== --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="card-title">🔥 Popular Restaurants</div>
        <a href="{{ route('customer.restaurants') }}" class="btn-kk btn-ghost-kk btn-sm-kk">
            View All <i class="fa fa-arrow-right"></i>
        </a>
    </div>
    <div class="row g-3 mb-4">
        @foreach($restaurants as $restaurant)
        <div class="col-md-4 col-sm-6">
            <x-restaurant-card :restaurant="$restaurant" />
        </div>
        @endforeach
    </div>

    {{-- ===== QUICK ORDER MENU ===== --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="card-title">⚡ Quick Order</div>
        <a href="{{ route('customer.items') }}" class="btn-kk btn-ghost-kk btn-sm-kk">
            All Menu <i class="fa fa-arrow-right"></i>
        </a>
    </div>
    <div class="row g-3">
        @foreach($menuItems as $item)
        <div class="col-md-3 col-6">
            <x-food-card :item="$item" />
        </div>
        @endforeach
    </div>

</div>


@push('styles')
    <style>
        /* ── Hero Section ── */
        .kk-hero {
            background: linear-gradient(135deg, #3D1461 0%, #7A1E8C 55%, #C81E7C 100%);
            border-radius: 0 0 24px 24px;
            padding: 16px 16px 18px;
            margin: -1px -1px 20px;
            position: relative;
            overflow: hidden;
        }
        .kk-hero::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 180px; height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
            pointer-events: none;
        }
        .kk-hero::after {
            content: '';
            position: absolute;
            bottom: -60px; right: 60px;
            width: 140px; height: 140px;
            border-radius: 50%;
            background: rgba(255,255,255,.05);
            pointer-events: none;
        }
        .kk-hero-location,
        .kk-hero-search,
        .kk-hero-slider,
        .kk-hero-slider-dots {
            position: relative;
            z-index: 1;
        }

        /* Location bar */
        .kk-hero-location {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .kk-hero-location-left {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }
        .kk-hero-pin {
            color: #fff;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .kk-hero-location-text { min-width: 0; }
        .kk-hero-location-label {
            color: #fff;
            font-weight: 700;
            font-size: .92rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .kk-hero-location-sub {
            color: rgba(255,255,255,.85);
            font-size: .74rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 220px;
        }
        .kk-hero-heart {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: rgba(255,255,255,.18);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex-shrink: 0;
            transition: background .2s ease;
        }
        .kk-hero-heart:hover { background: rgba(255,255,255,.3); color: #fff; }

        /* Search bar */
        .kk-hero-search {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border-radius: 50px;
            padding: 6px 6px 6px 16px;
            margin-bottom: 16px;
            box-shadow: 0 6px 20px rgba(0,0,0,.12);
        }
        .kk-hero-search-icon { color: #999; font-size: 1.15rem; flex-shrink: 0; }
        .kk-hero-search input {
            flex: 1;
            border: none;
            outline: none;
            font-size: .86rem;
            background: transparent;
            min-width: 0;
        }
        .kk-hero-search-btn {
            background: var(--pink, #FF3D8B);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 9px 16px;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            flex-shrink: 0;
            transition: background .2s ease;
        }
        .kk-hero-search-btn:hover { background: #e02d7a; }

        /* Slider */
        .kk-hero-slider {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            border-radius: 14px;
        }
        .kk-hero-slider::-webkit-scrollbar { display: none; }
        .kk-hero-slide {
            flex: 0 0 100%;
            scroll-snap-align: start;
            border-radius: 14px;
            overflow: hidden;
            display: block;
            line-height: 0;
        }
        .kk-hero-slide img {
            width: 100%;
            height: 130px;
            object-fit: cover;
            display: block;
        }
        .kk-hero-slider-dots {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 10px;
        }
        .kk-hero-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: rgba(255,255,255,.45);
            transition: var(--transition, .2s ease);
        }
        .kk-hero-dot.active {
            width: 16px;
            border-radius: 4px;
            background: #fff;
        }

        /* ── Existing styles ── */
        .product-card {
            align-items: center;
            gap: 12px;
        }
        .product-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .product-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .stat-info .label {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 140px;
        }
        .old-price {
            text-decoration: line-through;
            color: #999;
            font-weight: 400;
            margin-left: 4px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Sync dot indicators with the hero slider's scroll position.
        // Event-delegated + guarded against missing element (Livewire DOM timing).
        document.addEventListener('scroll', function (e) {
            const slider = document.getElementById('kkHeroSlider');
            if (!slider || e.target !== slider) return;

            const dots = document.querySelectorAll('#kkHeroSlider ~ .kk-hero-slider-dots .kk-hero-dot');
            if (!dots.length) return;

            const slideWidth = slider.clientWidth;
            const activeIndex = Math.round(slider.scrollLeft / slideWidth);

            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === activeIndex);
            });
        }, true);
    </script>
@endpush