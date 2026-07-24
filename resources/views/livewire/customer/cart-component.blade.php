{{-- resources/views/livewire/customer/cart-component.blade.php --}}
<div>

    {{-- ═══════════════════════════════════════════
         CART DRAWER OVERLAY
    ═══════════════════════════════════════════ --}}
    <div class="cart-overlay {{ $open ? 'open' : '' }}" wire:click="toggleCart">

        <div class="cart-drawer" wire:click.stop>

            {{-- Head --}}
            <div class="cart-head">
                <div>
                    <h3>
                        <i class="fa fa-shopping-bag" style="color:var(--pink)"></i>
                        আমার কার্ট
                    </h3>
                    <div style="font-size:12px; color:var(--text-3);">
                        {{ $this->count }} টি আইটেম
                    </div>
                </div>
                <button class="cart-close" wire:click="toggleCart">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            {{-- Items --}}
            <div class="cart-items">
                @forelse($items as $id => $item)
                <div class="cart-item">
                    <img
                        class="ci-img"
                        src="{{ $item['image_url'] ?? '' }}"
                        alt="{{ $item['name'] }}"
                        onerror="this.src='https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=80&q=60'"
                    >
                    <div style="flex:1; min-width:0;">
                        <div class="ci-name">
                            @if($item['emoji'] ?? null) {{ $item['emoji'] }} @endif
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
                        <div class="ci-price">৳{{ number_format(($item['price'] * $item['qty'])) }}</div>
                        <button
                            wire:click="removeItem({{ $id }})"
                            style="background:none; border:none; color:var(--danger); font-size:11px; cursor:pointer; margin-top:4px;"
                        >
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
                @empty
                <div class="text-center py-5" style="color:var(--text-3);">
                    <i class="fa fa-shopping-bag fa-3x mb-3" style="opacity:0.3; display:block;"></i>
                    <p style="font-size:14px;">কার্ট খালি আছে</p>
                </div>
                @endforelse
            </div>

            {{-- Footer --}}
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

                <button
                    class="btn-kk btn-primary-kk"
                    style="width:100%; justify-content:center; font-size:15px;"
                    wire:click="placeOrder"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="placeOrder">
                        <i class="fa fa-check-circle"></i> অর্ডার কনফার্ম করুন
                    </span>
                    <span wire:loading wire:target="placeOrder">
                        <i class="fa fa-spinner fa-spin"></i> প্রসেস হচ্ছে...
                    </span>
                </button>

                <button
                    wire:click="clearCart"
                    style="width:100%; background:none; border:none; color:var(--text-3); font-size:12px; cursor:pointer; margin-top:8px; padding:4px;"
                >
                    কার্ট খালি করুন
                </button>
            </div>
            @endif

        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         FLOATING CART BUTTON
    ═══════════════════════════════════════════ --}}
    <button
        class="cart-btn"
        style="display:flex;"
        wire:click="toggleCart"
    >
        <i class="fa fa-shopping-bag"></i>
        <div class="cart-count">{{ $this->count }}</div>
    </button>

    @if($showConflict)
    <div
        style="
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
        "
    >
        <div
            style="
                background: white;
                border-radius: 16px;
                padding: 28px 24px;
                text-align: center;
                width: min(420px, 90vw);
                box-shadow: 0 20px 60px rgba(0,0,0,.2);
                animation: modalPop 0.25s ease;
            "
        >
            {{-- Icon --}}
            <div style="
                width:56px; height:56px; border-radius:50%;
                background:var(--pink-soft);
                display:flex; align-items:center; justify-content:center;
                margin:0 auto 16px;
            ">
                <i class="fa fa-exclamation-triangle fa-xl" style="color:var(--pink);"></i>
            </div>

            {{-- Text --}}
            <h5 style="font-size:16px; font-weight:800; margin-bottom:8px; color:var(--text);">
                ভিন্ন রেস্তোরাঁর আইটেম
            </h5>
            <p style="font-size:13px; color:var(--text-2); margin-bottom:20px; line-height:1.6;">
                কার্টে অন্য রেস্তোরাঁর আইটেম আছে।<br>
                কার্ট <strong>খালি করে</strong> নতুন আইটেম যোগ করবেন?
            </p>

            {{-- Pending item preview --}}
            @if(! empty($pendingItem))
            <div style="
                background: var(--pink-ultra-soft);
                border: 1px solid var(--pink-mid);
                border-radius: 10px;
                padding: 10px 14px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
                text-align: left;
            ">
                <span style="font-size:24px;">{{ $pendingItem['emoji'] ?? '🍽️' }}</span>
                <div>
                    <div style="font-size:13px; font-weight:700; color:var(--text);">
                        {{ $pendingItem['name'] }}
                    </div>
                    <div style="font-size:12px; color:var(--pink); font-weight:700;">
                        ৳{{ number_format($pendingItem['price']) }}
                    </div>
                </div>
            </div>
            @endif

            {{-- Buttons --}}
            <div style="display:flex; gap:10px; justify-content:center;">

                {{-- হ্যাঁ — কার্ট খালি করে add করো --}}
                <button
                    wire:click="confirmClearAndAdd"
                    wire:loading.attr="disabled"
                    class="btn-kk btn-primary-kk"
                    style="flex:1; justify-content:center;"
                >
                    <span wire:loading.remove wire:target="confirmClearAndAdd">
                        <i class="fa fa-trash"></i> হ্যাঁ, খালি করুন
                    </span>
                    <span wire:loading wire:target="confirmClearAndAdd">
                        <i class="fa fa-spinner fa-spin"></i>
                    </span>
                </button>

                {{-- না — আগের কার্ট রাখো --}}
                <button
                    wire:click="cancelConflict"
                    style="
                        flex:1;
                        background: var(--bg);
                        border: 1.5px solid var(--border);
                        border-radius: var(--radius-sm);
                        padding: 8px 16px;
                        font-family: inherit;
                        font-size: 14px;
                        font-weight: 600;
                        color: var(--text-2);
                        cursor: pointer;
                    "
                >
                    না, রাখুন
                </button>
            </div>
        </div>
    </div>

    {{-- Modal pop animation --}}
    <style>
        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.92) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>
    @endif

</div>