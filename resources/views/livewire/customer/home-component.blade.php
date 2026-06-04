{{-- resources/views/livewire/customer/customer-home.blade.php --}}
<div>

    {{-- ===== HERO BANNER ===== --}}
    <div class="hero-banner">
        <div class="hero-text">
            <h2>আপনার পছন্দের খাবার<br><span>দ্রুত ডেলিভারিতে!</span></h2>
            <p>Gazipur ও Dhaka-র সেরা রেস্তোরাঁ থেকে অর্ডার করুন</p>
            <div class="hero-search">
                <input
                    wire:model="searchQuery"
                    wire:keydown.enter="searchFood"
                    placeholder="খাবার বা রেস্তোরাঁ খুঁজুন..."
                >
                <button wire:click="searchFood">
                    <i class="fa fa-search"></i> খুঁজুন
                </button>
            </div>
        </div>
        <img
            src="https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=300&q=80"
            class="hero-img"
            alt="food"
        >
    </div>

    {{-- ===== STATS ===== --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card sc-pink">
                <div class="stat-icon"><i class="fa fa-store"></i></div>
                <div class="stat-info"><div class="num">500+</div><div class="label">রেস্তোরাঁ পার্টনার</div>
                <div class="change up"><i class="fa fa-arrow-up"></i> ১২টি নতুন</div></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card sc-green">
                <div class="stat-icon"><i class="fa fa-clock"></i></div>
                <div class="stat-info"><div class="num">28 মিনিট</div><div class="label">গড় ডেলিভারি সময়</div>
                <div class="change up"><i class="fa fa-arrow-up"></i> সেরা সময়!</div></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card sc-orange">
                <div class="stat-icon"><i class="fa fa-tag"></i></div>
                <div class="stat-info"><div class="num">৳0</div><div class="label">প্রথম অর্ডারে ফি</div>
                <div class="change up"><i class="fa fa-gift"></i> Welcome অফার</div></div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card sc-blue">
                <div class="stat-icon"><i class="fa fa-headset"></i></div>
                <div class="stat-info"><div class="num">24/7</div><div class="label">কাস্টমার সাপোর্ট</div>
                <div class="change up"><i class="fa fa-check"></i> সবসময় আছি</div></div>
            </div>
        </div>
    </div>

    {{-- ===== CATEGORY PILLS ===== --}}
    <div class="cat-pills">
        <div class="cat-pill active"><span class="emoji">🍽️</span> সব</div>
        <div class="cat-pill"><span class="emoji">🍚</span> ভাত</div>
        <div class="cat-pill"><span class="emoji">🍗</span> বিরিয়ানি</div>
        <div class="cat-pill"><span class="emoji">🍔</span> বার্গার</div>
        <div class="cat-pill"><span class="emoji">🍕</span> পিৎজা</div>
        <div class="cat-pill"><span class="emoji">🍜</span> নুডলস</div>
        <div class="cat-pill"><span class="emoji">☕</span> ড্রিংকস</div>
    </div>

    {{-- ===== POPULAR RESTAURANTS ===== --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="card-title">🔥 জনপ্রিয় রেস্তোরাঁ</div>
        <a href="{{ route('customer.restaurants') }}" class="btn-kk btn-ghost-kk btn-sm-kk">
            সব দেখুন <i class="fa fa-arrow-right"></i>
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
        <div class="card-title">⚡ দ্রুত অর্ডার করুন</div>
        <a href="{{ route('customer.items') }}" class="btn-kk btn-ghost-kk btn-sm-kk">
            সব মেনু <i class="fa fa-arrow-right"></i>
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