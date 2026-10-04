{{-- resources/views/livewire/customer/customer-home.blade.php --}}
<div>

    {{-- ===== OFFER POPUP (admin theke manage kora, first visit / home page open) ===== --}}
    @if($offerPopup)
        <div wire:ignore id="kkOfferPopup" class="kk-offer-overlay" aria-hidden="true"
             data-key="offer_{{ $offerPopup->id }}_{{ $offerPopup->updated_at?->timestamp }}"
             data-hours="{{ (int) $offerPopup->repeat_hours }}">
            <div class="kk-offer-box" role="dialog" aria-modal="true" aria-labelledby="kkOfferTitle">
                <button type="button" class="kk-offer-close" id="kkOfferClose" aria-label="Close">&times;</button>

                <div class="kk-offer-top">
                    @if($offerPopup->badge)
                        <div class="kk-offer-badge">{{ $offerPopup->badge }}</div>
                    @endif
                    <div class="kk-offer-percent">{{ $offerPopup->highlight }}</div>
                    @if($offerPopup->highlight_sub)
                        <div class="kk-offer-off">{{ $offerPopup->highlight_sub }}</div>
                    @endif
                </div>

                <div class="kk-offer-body">
                    <h3 class="kk-offer-title" id="kkOfferTitle">{{ $offerPopup->title }}</h3>

                    @if($offerPopup->message)
                        <p class="kk-offer-text">{{ $offerPopup->message }}</p>
                    @endif

                    @if(count($offerPopup->chips_list))
                        <div class="kk-offer-chips">
                            @foreach($offerPopup->chips_list as $chip)
                                <span class="kk-offer-chip">{{ $chip }}</span>
                            @endforeach
                        </div>
                    @endif

                    <a href="{{ $offerPopup->button_url ?: route('customer.restaurants') }}"
                       class="kk-offer-cta" id="kkOfferCta">
                        {{ $offerPopup->button_text }} <i class="fa fa-arrow-right"></i>
                    </a>
                    <button type="button" class="kk-offer-later" id="kkOfferLater">পরে দেখব</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== HERO SECTION: Location + Search + Slider ===== --}}
    <div class="kk-hero {{ $sliders->isNotEmpty() ? 'kk-hero--has-slider' : '' }}">

        {{-- Promo slider (full-cover background of the hero) --}}
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
        @endif

        {{-- Location bar --}}
        <div class="kk-hero-location">
            <div class="kk-hero-location-left">
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
            <a href="{{ route('customer.addresses') }}" class="kk-hero-map" aria-label="Saved addresses">
                <i class="fa fa-map-marker-alt kk-hero-pin"></i>
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

        @if($sliders->count() > 1)
            <div class="kk-hero-slider-dots">
                @foreach($sliders as $i => $slider)
                    <span class="kk-hero-dot {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}"></span>
                @endforeach
            </div>
        @endif

    </div>

    {{-- ===== FEATURED PRODUCTS ===== --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="card-title">🛍️ Featured Products</div>
    </div>
    <div class="row g-3 mb-4">
        @php $colors = ['sc-pink', 'sc-green', 'sc-orange', 'sc-blue']; @endphp
        @forelse($products as $index => $product)
            <div class="col-md-3 col-12" wire:key="featured-{{ $product->id }}">
                {{-- Whole card opens the detail page. Add to cart lives there. --}}
                <a href="{{ route('customer.product', $product->slug) }}"
                   class="stat-card {{ $colors[$index % 4] }} product-card"
                   style="text-decoration:none; color:inherit; cursor:pointer;">
                    <div class="stat-icon product-icon">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                        @elseif($product->emoji)
                            <span style="font-size:26px;">{{ $product->emoji }}</span>
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
                </a>
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
        /* ── Offer Popup ── */
        .kk-offer-overlay {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(20, 5, 35, .65);
            backdrop-filter: blur(3px);
            opacity: 0;
            transition: opacity .25s ease;
        }
        .kk-offer-overlay.show { display: flex; }
        .kk-offer-overlay.visible { opacity: 1; }
        .kk-offer-box {
            position: relative;
            width: 100%;
            max-width: 380px;
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(0,0,0,.35);
            transform: translateY(24px) scale(.95);
            transition: transform .3s cubic-bezier(.2,.9,.3,1.2);
        }
        .kk-offer-overlay.visible .kk-offer-box { transform: translateY(0) scale(1); }
        .kk-offer-close {
            position: absolute;
            top: 10px; right: 12px;
            width: 32px; height: 32px;
            border: none;
            border-radius: 50%;
            background: rgba(255,255,255,.22);
            color: #fff;
            font-size: 1.4rem;
            line-height: 1;
            cursor: pointer;
            z-index: 2;
            transition: background .2s ease;
        }
        .kk-offer-close:hover { background: rgba(255,255,255,.4); }
        .kk-offer-top {
            position: relative;
            text-align: center;
            padding: 28px 20px 22px;
            color: #fff;
            background: linear-gradient(135deg, #3D1461 0%, #7A1E8C 55%, #C81E7C 100%);
            overflow: hidden;
        }
        .kk-offer-top::before {
            content: '';
            position: absolute;
            top: -50px; right: -50px;
            width: 170px; height: 170px;
            border-radius: 50%;
            background: rgba(255,255,255,.08);
        }
        .kk-offer-top::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -40px;
            width: 150px; height: 150px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
        }
        .kk-offer-top > * { position: relative; z-index: 1; }
        .kk-offer-badge {
            display: inline-block;
            background: rgba(255,255,255,.2);
            border-radius: 50px;
            padding: 5px 14px;
            font-size: .8rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .kk-offer-percent {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: .5px;
        }
        .kk-offer-off {
            font-size: 1.15rem;
            font-weight: 700;
            opacity: .95;
        }
        .kk-offer-body { padding: 20px 22px 22px; text-align: center; }
        .kk-offer-title {
            margin: 0 0 8px;
            font-size: 1.2rem;
            font-weight: 700;
            color: #3D1461;
        }
        .kk-offer-text {
            margin: 0 0 14px;
            font-size: .88rem;
            color: #666;
            line-height: 1.5;
        }
        .kk-offer-chips {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
            margin-bottom: 18px;
        }
        .kk-offer-chip {
            background: #F7F3FC;
            color: #7A1E8C;
            border-radius: 50px;
            padding: 5px 12px;
            font-size: .76rem;
            font-weight: 600;
        }
        .kk-offer-cta {
            display: block;
            width: 100%;
            padding: 12px 16px;
            border-radius: 50px;
            background: var(--pink, #FF3D8B);
            color: #fff;
            font-size: .95rem;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(255,61,139,.35);
            transition: background .2s ease, transform .2s ease;
        }
        .kk-offer-cta:hover { background: #e02d7a; color: #fff; transform: translateY(-1px); }
        .kk-offer-later {
            margin-top: 10px;
            border: none;
            background: transparent;
            color: #999;
            font-size: .8rem;
            cursor: pointer;
        }
        .kk-offer-later:hover { color: #666; }

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
        .kk-hero-slider-dots {
            position: relative;
            z-index: 2;
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
        .kk-hero-map {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: rgba(255,255,255,.18);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex-shrink: 0;
            text-decoration: none;
            transition: background .2s ease;
        }
        .kk-hero-map:hover { background: rgba(255,255,255,.3); color: #fff; }

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

        /* Slider = full-cover background of .kk-hero */
        .kk-hero--has-slider {
            min-height: 230px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #3D1461; /* fallback while images load */
        }
        .kk-hero--has-slider::before,
        .kk-hero--has-slider::after {
            width: auto; height: auto;
            top: 0; right: 0; bottom: 0; left: 0;
            border-radius: 0;
        }
        .kk-hero--has-slider::before {
            background: linear-gradient(180deg, rgba(40,10,60,.65) 0%, rgba(40,10,60,.15) 55%, rgba(40,10,60,.55) 100%);
            z-index: 1;
        }
        .kk-hero--has-slider::after { display: none; }

        .kk-hero-slider {
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            z-index: 0;
            display: flex;
            align-items: stretch;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            border-radius: inherit;
        }
        .kk-hero-slider::-webkit-scrollbar { display: none; }
        .kk-hero-slide {
            position: relative;
            flex: 0 0 100%;
            align-self: stretch;
            scroll-snap-align: start;
            display: block;
            overflow: hidden;
        }
        .kk-hero-slide img {
            position: absolute;
            top: 0; left: 0;
            width: 100% !important;
            height: 100% !important;
            max-width: none;
            max-height: none;
            object-fit: cover;     /* image poora hero cover kore */
            object-position: center;
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

        // Auto-play: har 4 second e porer slide e jabe (hover korle pause).
        (function () {
            if (window.__kkHeroAutoplay) return;
            window.__kkHeroAutoplay = true;
            let paused = false;
            document.addEventListener('mouseover', function (e) {
                if (e.target.closest && e.target.closest('.kk-hero')) paused = true;
            });
            document.addEventListener('mouseout', function () { paused = false; });

            setInterval(function () {
                const slider = document.getElementById('kkHeroSlider');
                if (!slider || paused) return;
                const count = slider.children.length;
                if (count < 2) return;
                const w = slider.clientWidth;
                const idx = Math.round(slider.scrollLeft / w);
                slider.scrollTo({ left: ((idx + 1) % count) * w, behavior: 'smooth' });
            }, 4000);
        })();

        // ── Offer popup (admin theke dynamic) ──
        // data-key   : popup id + updated_at. Admin edit korle key bodle jay,
        //              tai customer abar notun popup dekhbe.
        // data-hours : ekbar dekhar por koto ghonta abar dekhabe (0 = protibar).
        (function () {
            function storageKey(overlay) {
                return 'kk_offer_popup_seen_' + (overlay.dataset.key || 'default');
            }
            function alreadySeen(overlay) {
                const hours = parseInt(overlay.dataset.hours || '0', 10);
                if (hours <= 0) return false;
                try {
                    const t = parseInt(localStorage.getItem(storageKey(overlay)) || '0', 10);
                    return !!t && (Date.now() - t) < hours * 3600 * 1000;
                } catch (e) { return false; }
            }
            function markSeen(overlay) {
                try { localStorage.setItem(storageKey(overlay), String(Date.now())); } catch (e) {}
            }

            function closePopup(overlay) {
                overlay.classList.remove('visible');
                overlay.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                setTimeout(function () { overlay.classList.remove('show'); }, 250);
                markSeen(overlay);
            }

            function initOfferPopup() {
                const overlay = document.getElementById('kkOfferPopup');   // popup na thakle kichu hobe na
                if (!overlay || overlay.dataset.kkInit === '1') return;
                overlay.dataset.kkInit = '1';

                overlay.querySelector('#kkOfferClose').addEventListener('click', function () { closePopup(overlay); });
                overlay.querySelector('#kkOfferLater').addEventListener('click', function () { closePopup(overlay); });
                overlay.querySelector('#kkOfferCta').addEventListener('click', function () { markSeen(overlay); });
                overlay.addEventListener('click', function (e) { if (e.target === overlay) closePopup(overlay); });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && overlay.classList.contains('show')) closePopup(overlay);
                });

                if (alreadySeen(overlay)) return;

                setTimeout(function () {
                    overlay.classList.add('show');
                    overlay.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                    requestAnimationFrame(function () { overlay.classList.add('visible'); });
                }, 800);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initOfferPopup);
            } else {
                initOfferPopup();
            }
            // wire:navigate / SPA-style navigation e home e firle abar check korbe
            document.addEventListener('livewire:navigated', initOfferPopup);
        })();
    </script>
@endpush