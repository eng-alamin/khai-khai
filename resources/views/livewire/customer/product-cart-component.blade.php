{{-- resources/views/livewire/customer/product-cart-component.blade.php --}}
{{-- Separate cart for admin products. Vendor food cart is cart-component. --}}
<div>

    <div class="cart-overlay {{ $open ? 'open' : '' }}" wire:click="toggleCart">
        <div class="cart-drawer" wire:click.stop>

            <div class="cart-head">
                <div>
                    <h3>
                        <i class="fa fa-box" style="color:var(--pink)"></i>
                        প্রোডাক্ট কার্ট
                    </h3>
                    <div style="font-size:12px; color:var(--text-3);">
                        {{ $this->count }} টি আইটেম
                    </div>
                </div>
                <button class="cart-close" wire:click="toggleCart">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="cart-items">
                @forelse($items as $id => $item)
                <div class="cart-item" wire:key="pcart-{{ $id }}">
                    @if(! empty($item['image_url']))
                        <img class="ci-img" src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}"
                             onerror="this.style.display='none'">
                    @endif
                    <div style="flex:1; min-width:0;">
                        <div class="ci-name">
                            @if(! empty($item['emoji'])) {{ $item['emoji'] }} @endif
                            {{ $item['name'] }}
                        </div>
                        <div class="cart-qty">
                            <button wire:click="decrement({{ $id }})">
                                <i class="fa fa-minus" style="font-size:10px;"></i>
                            </button>
                            <span>{{ $item['qty'] }}</span>
                            <button wire:click="increment({{ $id }})">
                                <i class="fa fa-plus" style="font-size:10px;"></i>
                            </button>
                        </div>
                    </div>
                    <div style="text-align:right; flex-shrink:0;">
                        <div class="ci-price">৳{{ number_format($item['price'] * $item['qty']) }}</div>
                        <button wire:click="removeItem({{ $id }})"
                                style="background:none; border:none; color:var(--danger); font-size:11px; cursor:pointer; margin-top:4px;">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
                @empty
                <div class="text-center py-5" style="color:var(--text-3);">
                    <i class="fa fa-box fa-3x mb-3" style="opacity:0.3; display:block;"></i>
                    <p style="font-size:14px;">প্রোডাক্ট কার্ট খালি আছে</p>
                </div>
                @endforelse
            </div>

            @if(count($items) > 0)
            <div class="cart-footer">
                <div class="cart-total-row">
                    <span>সাবটোটাল</span>
                    <span>৳{{ number_format($this->subtotal) }}</span>
                </div>
                <div class="cart-total-row">
                    <span>ডেলিভারি চার্জ</span>
                    <span>৳{{ number_format($this->deliveryFee) }}</span>
                </div>
                <div class="cart-grand">
                    <span>মোট</span>
                    <span>৳{{ number_format($this->total) }}</span>
                </div>

                <button class="btn-kk btn-primary-kk"
                        style="width:100%; justify-content:center; font-size:15px;"
                        wire:click="placeOrder"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="placeOrder">
                        <i class="fa fa-check-circle"></i> অর্ডার কনফার্ম করুন
                    </span>
                    <span wire:loading wire:target="placeOrder">
                        <i class="fa fa-spinner fa-spin"></i> প্রসেস হচ্ছে...
                    </span>
                </button>

                <button wire:click="clearCart"
                        style="width:100%; background:none; border:none; color:var(--text-3); font-size:12px; cursor:pointer; margin-top:8px; padding:4px;">
                    কার্ট খালি করুন
                </button>
            </div>
            @endif

        </div>
    </div>

    {{-- Floating button sits above the food cart button (food: bottom:80px) --}}
    <button class="cart-btn"
            style="display:flex; bottom:146px; background:var(--dark, #1f2937);"
            wire:click="toggleCart"
            title="প্রোডাক্ট কার্ট">
        <i class="fa fa-box"></i>
        <div class="cart-count" style="background:var(--pink);">{{ $this->count }}</div>
    </button>

</div>
