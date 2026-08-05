{{-- resources/views/livewire/customer/customer-home.blade.php --}}
<div>

    {{-- ===== HERO BANNER ===== --}}
    <div class="hero-banner">
        <div class="hero-text">
            <h2>Your Favorite Food<br><span>Delivered Fast!</span></h2>
            <p>Order from the best restaurants in Gazipur & Dhaka</p>
            <div class="hero-search">
                <input
                    wire:model="searchQuery"
                    wire:keydown.enter="searchFood"
                    placeholder="Search food or restaurant..."
                >
                <button wire:click="searchFood">
                    <i class="fa fa-search"></i> Search
                </button>
            </div>
        </div>
        <img
            src="https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=300&q=80"
            class="hero-img"
            alt="food"
        >
    </div>

    {{-- ===== FEATURED PRODUCTS ===== --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="card-title">🛍️ Featured Products</div>
    </div>
    <div class="row g-3 mb-4">
        @php $colors = ['sc-pink', 'sc-green', 'sc-orange', 'sc-blue']; @endphp
        @forelse($products as $index => $product)
            <div class="col-md-3 col-6">
                <div class="stat-card {{ $colors[$index % 4] }} product-card">
                    <div class="stat-icon product-icon">
                        <img
                            src="{{ $product->image_url ?? 'https://via.placeholder.com/80?text=' . urlencode($product->emoji ?? '🛍') }}"
                            alt="{{ $product->name }}"
                        >
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