{{-- resources/views/livewire/customer/product-detail-component.blade.php --}}
<div>

    <a href="{{ route('customer.home') }}" class="btn-kk btn-ghost-kk btn-sm-kk mb-3">
        <i class="fa fa-arrow-left"></i> ফিরে যান
    </a>

    <div class="card">
        <div class="row g-4 align-items-center">

            {{-- Image --}}
            <div class="col-md-5">
                <div style="border-radius:16px; overflow:hidden; background:var(--pink-ultra-soft); aspect-ratio:1/1; display:flex; align-items:center; justify-content:center;">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}"
                             alt="{{ $product->name }}"
                             style="width:100%; height:100%; object-fit:cover;"
                             onerror="this.style.display='none'">
                    @elseif($product->emoji)
                        <span style="font-size:96px;">{{ $product->emoji }}</span>
                    @else
                        <i class="fa fa-shopping-bag fa-4x" style="color:var(--pink); opacity:.5;"></i>
                    @endif
                </div>
            </div>

            {{-- Info --}}
            <div class="col-md-7">
                @if($product->category)
                    <span class="badge-pink" style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; margin-bottom:8px;">
                        {{ $product->category->emoji }} {{ $product->category->name }}
                    </span>
                @endif

                <h2 style="font-size:24px; font-weight:800; margin-bottom:8px;">
                    @if($product->emoji) {{ $product->emoji }} @endif
                    {{ $product->name }}
                </h2>

                <div style="display:flex; align-items:baseline; gap:10px; margin-bottom:14px; flex-wrap:wrap;">
                    <span style="font-size:28px; font-weight:800; color:var(--pink); font-family:'Nunito',sans-serif;">
                        ৳{{ number_format($product->price, 0) }}
                    </span>
                    @if($product->compare_price && $product->compare_price > $product->price)
                        <span style="text-decoration:line-through; color:var(--text-3);">
                            ৳{{ number_format($product->compare_price, 0) }}
                        </span>
                        <span class="badge-pink" style="padding:2px 8px; border-radius:20px; font-size:12px; font-weight:700;">
                            -{{ round((($product->compare_price - $product->price) / $product->compare_price) * 100) }}%
                        </span>
                    @endif
                </div>

                @if($product->description)
                    <p style="font-size:14px; color:var(--text-2); line-height:1.7; margin-bottom:18px;">
                        {{ $product->description }}
                    </p>
                @endif

                <button type="button"
                        class="btn-kk btn-primary-kk"
                        style="font-size:15px; padding:10px 22px;"
                        wire:click="addToCart"
                        wire:loading.attr="disabled"
                        wire:target="addToCart">
                    <span wire:loading.remove wire:target="addToCart">
                        <i class="fa fa-plus"></i> Add to cart
                    </span>
                    <span wire:loading wire:target="addToCart">
                        <i class="fa fa-spinner fa-spin"></i> যোগ হচ্ছে...
                    </span>
                </button>
            </div>

        </div>
    </div>

</div>
