<div>

    {{-- ══════════════════════════════════════════════
         HEADER — অনলাইন স্ট্যাটাস + আজকের আয়
         ══════════════════════════════════════════════ --}}
    <div class="card mb-4" style="padding:16px 20px;">
        <div class="d-flex align-items-center justify-content-between">

            {{-- আজকের আয় --}}
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-3);margin-bottom:4px;">
                    আজকের আয়
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size:22px;font-weight:900;color:var(--success);">
                        {{ $todayEarnings['total_taka'] }}
                    </span>
                    <span class="badge-kk" style="background:#dcfce7;color:#166534;">
                        {{ $this->toBanglaNumber($todayEarnings['deliveries']) }}টি ডেলিভারি
                    </span>
                </div>
            </div>

            {{-- অনলাইন টগল --}}
            <button
                type="button"
                wire:click="toggleOnline"
                class="d-flex align-items-center gap-2"
                style="background:none;border:none;cursor:pointer;padding:8px 14px;border-radius:999px;border:1.5px solid {{ $isOnline ? '#16a34a' : '#d1d5db' }};transition:all .2s;"
            >
                <span style="width:9px;height:9px;border-radius:50%;background:{{ $isOnline ? 'var(--success)' : '#9ca3af' }};display:inline-block;"></span>
                <span style="font-size:13px;color:{{ $isOnline ? 'var(--success)' : 'var(--text-3)' }};font-weight:700;">
                    {{ $isOnline ? 'অনলাইন' : 'অফলাইন' }}
                </span>
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         ONGOING — আমার চলমান ডেলিভারি
         ══════════════════════════════════════════════ --}}
    <div class="mb-2" style="font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);">
        🛵 চলমান ডেলিভারি
    </div>

    <div class="d-flex flex-column gap-3 mb-4">
        @forelse($ongoingOrders as $order)
            <div class="card" wire:key="ongoing-{{ $order['id'] }}" style="padding:18px 20px;border-left:4px solid #3b82f6;" x-data="{ showMap: false }">

                {{-- Top row --}}
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div style="font-weight:800;font-size:16px;color:#1e40af;">
                        #{{ $order['order_number'] }}
                    </div>
                    <span class="badge-kk" style="background:{{ $order['status_bg'] }};color:{{ $order['status_color'] }};">
                        {{ $order['status_label'] }}
                    </span>
                </div>

                {{-- Restaurant info --}}
                <div class="info-row mb-1">
                    <span class="info-icon">🏠</span>
                    <span style="font-weight:700;">{{ $order['restaurant'] }}</span>
                    @if($order['restaurant_phone'])
                        <a href="tel:{{ $order['restaurant_phone'] }}" class="call-btn ms-auto">
                            📞 কল
                        </a>
                    @endif
                </div>

                {{-- Customer info --}}
                <div class="info-row mb-1">
                    <span class="info-icon">👤</span>
                    <span>{{ $order['customer'] }}</span>
                    @if($order['customer_phone'])
                        <a href="tel:{{ $order['customer_phone'] }}" class="call-btn ms-auto">
                            📞 কল
                        </a>
                    @endif
                </div>

                {{-- Address --}}
                <div class="info-row mb-3">
                    <span class="info-icon">📍</span>
                    <span style="color:var(--text-2);">{{ $order['address'] }}</span>
                </div>

                {{-- Items --}}
                <div class="items-box mb-3">
                    <span style="font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px;">
                        আইটেম
                    </span>
                    <div style="font-size:13px;color:var(--text-2);margin-top:4px;">{{ $order['items'] }}</div>
                </div>

                {{-- Route Map (collapsible) --}}
                <div class="mb-3">
                    <button
                        type="button"
                        @click="showMap = !showMap"
                        class="btn-kk"
                        style="background:#eff6ff;color:#1d4ed8;width:100%;justify-content:center;"
                    >
                        <span x-show="!showMap">🗺️ রুট দেখুন</span>
                        <span x-show="showMap" style="display:none;">🔼 রুট বন্ধ করুন</span>
                    </button>

                    <div
                        x-show="showMap"
                        x-cloak
                        x-effect="if (showMap) { $nextTick(() => window.dispatchEvent(new CustomEvent('kk-map-shown', { detail: { orderId: {{ $order['id'] }} } }))) }"
                        style="margin-top:10px;"
                    >
                        @livewire('rider.order-route-map', ['orderId' => $order['id']], key('rider-map-'.$order['id']))
                    </div>
                </div>

                {{-- Footer --}}
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div style="font-size:20px;font-weight:900;">{{ $order['total'] }}</div>
                        <div style="font-size:11px;color:var(--text-3);">
                            আপনার আয়: <strong style="color:var(--success);">{{ $order['delivery_fee'] }}</strong>
                        </div>
                    </div>

                    <div class="d-flex gap-2 align-items-center">
                        {{-- Payment badge --}}
                        @if($order['payment_method'] === 'cash_on_delivery')
                            <span class="badge-kk" style="background:#fef3c7;color:#92400e;font-size:11px;">
                                💵 ক্যাশ কালেক্ট করুন
                            </span>
                        @else
                            <span class="badge-kk" style="background:#dcfce7;color:#166534;font-size:11px;">
                                ✅ পেমেন্ট হয়েছে
                            </span>
                        @endif

                        <button
                            class="btn-kk"
                            style="background:var(--success);color:#fff;"
                            wire:click="completeDelivery({{ $order['id'] }})"
                            wire:confirm="ডেলিভারি সম্পন্ন হয়েছে নিশ্চিত করুন?"
                            wire:loading.attr="disabled"
                            wire:target="completeDelivery({{ $order['id'] }})"
                        >
                            <span wire:loading.remove wire:target="completeDelivery({{ $order['id'] }})">
                                ✅ ডেলিভারি দিয়েছি
                            </span>
                            <span wire:loading wire:target="completeDelivery({{ $order['id'] }})">
                                <span class="spinner"></span>
                            </span>
                        </button>
                    </div>
                </div>

            </div>
        @empty
            <div class="card text-center py-4" style="color:var(--text-3);">
                <div style="font-size:48px;" class="mb-2">🛵</div>
                <div style="font-weight:700;color:var(--text-2);">এখন কোনো চলমান ডেলিভারি নেই</div>
            </div>
        @endforelse
    </div>

    {{-- ══════════════════════════════════════════════
         AVAILABLE — পিকআপের জন্য অপেক্ষারত অর্ডার
         ══════════════════════════════════════════════ --}}
    <div class="d-flex align-items-center gap-2 mb-2">
        <span style="font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--text-3);">
            📦 নতুন অর্ডার
        </span>
        @if(!$isOnline)
            <span class="badge-kk" style="background:#f3f4f6;color:var(--text-3);font-size:11px;">
                অনলাইন হলে দেখা যাবে
            </span>
        @endif
    </div>

    <div class="d-flex flex-column gap-3">
        @forelse($availableOrders as $order)
            <div class="card" wire:key="avail-{{ $order['id'] }}" style="padding:18px 20px;border-left:4px solid var(--warning);">

                {{-- Top row --}}
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div style="font-weight:800;font-size:16px;color:#92400e;">
                        #{{ $order['order_number'] }}
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span style="font-size:12px;color:var(--text-3);">
                            ⏱ {{ $order['time_label'] }}
                        </span>
                        <span class="badge-kk" style="background:#fef3c7;color:#92400e;">
                            পিকআপ পেন্ডিং
                        </span>
                    </div>
                </div>

                {{-- Restaurant --}}
                <div class="info-row mb-1">
                    <span class="info-icon">🏠</span>
                    <span style="font-weight:700;">{{ $order['restaurant'] }}</span>
                </div>

                {{-- Delivery address --}}
                <div class="info-row mb-3">
                    <span class="info-icon">📍</span>
                    <span style="color:var(--text-2);">{{ $order['address'] }}</span>
                </div>

                {{-- Items summary --}}
                <div class="items-box mb-3">
                    <span style="font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.4px;">
                        আইটেম
                    </span>
                    <div style="font-size:13px;color:var(--text-2);margin-top:4px;">{{ $order['items'] }}</div>
                </div>

                {{-- Footer --}}
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div style="font-size:20px;font-weight:900;">{{ $order['total'] }}</div>
                        <div style="font-size:11px;color:var(--text-3);">
                            ডেলিভারি আয়: <strong style="color:var(--success);">{{ $order['delivery_fee'] }}</strong>
                        </div>
                    </div>

                    <button
                        class="btn-kk"
                        style="background:var(--warning);color:#fff;"
                        wire:click="acceptDelivery({{ $order['id'] }})"
                        wire:loading.attr="disabled"
                        wire:target="acceptDelivery({{ $order['id'] }})"
                    >
                        <span wire:loading.remove wire:target="acceptDelivery({{ $order['id'] }})">
                            🛵 অ্যাকসেপ্ট করুন
                        </span>
                        <span wire:loading wire:target="acceptDelivery({{ $order['id'] }})">
                            <span class="spinner"></span>
                        </span>
                    </button>
                </div>

            </div>
        @empty
            @if($isOnline)
                <div class="card text-center py-4" style="color:var(--text-3);">
                    <div style="font-size:48px;" class="mb-2">🎉</div>
                    <div style="font-weight:700;color:var(--text-2);">এখন কোনো অর্ডার অপেক্ষায় নেই</div>
                    <div style="font-size:13px;margin-top:4px;">নতুন অর্ডার আসলে এখানে দেখা যাবে।</div>
                </div>
            @else
                <div class="card text-center py-4" style="color:var(--text-3);">
                    <div style="font-size:48px;" class="mb-2">😴</div>
                    <div style="font-weight:700;color:var(--text-2);">আপনি এখন অফলাইন</div>
                    <div style="font-size:13px;margin-top:4px;">অর্ডার পেতে উপরে অনলাইন বাটন চাপুন।</div>
                </div>
            @endif
        @endforelse
    </div>

</div>

@push('styles')
<style>
    :root {
        --success: #16a34a;
        --warning: #f59e0b;
        --danger: #ef4444;
        --text-1: #1f2937;
        --text-2: #4b5563;
        --text-3: #9ca3af;
        --border: #e5e7eb;
        --radius: 14px;
        --shadow: 0 2px 10px rgba(0,0,0,.06);
    }

    .card {
        background: #fff;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        padding: 18px 20px;
        width: 100%;
    }

    .badge-kk {
        display: inline-flex; align-items: center;
        padding: 5px 12px; border-radius: 999px;
        font-size: 12px; font-weight: 700; white-space: nowrap;
    }

    .btn-kk {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 6px; padding: 9px 16px; border-radius: 10px;
        font-size: 13px; font-weight: 700; border: none;
        cursor: pointer; white-space: nowrap;
        transition: opacity .15s, transform .1s; text-decoration: none;
    }
    .btn-kk:hover   { opacity: .88; }
    .btn-kk:active  { transform: scale(.97); }
    .btn-kk:disabled { opacity: .5; cursor: not-allowed; }

    /* info row */
    .info-row {
        display: flex; align-items: flex-start; gap: 8px;
        font-size: 13px; color: var(--text-2);
    }
    .info-icon { flex-shrink: 0; font-size: 14px; margin-top: 1px; }

    /* call button */
    .call-btn {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 999px;
        background: #eff6ff; color: #1d4ed8;
        font-size: 11px; font-weight: 700; text-decoration: none;
        white-space: nowrap; flex-shrink: 0;
        transition: background .15s;
    }
    .call-btn:hover { background: #dbeafe; }

    /* items box */
    .items-box {
        background: #f9fafb; border: 1px solid var(--border);
        border-radius: 10px; padding: 10px 14px;
    }

    /* spinner */
    .spinner {
        display: inline-block;
        width: 14px; height: 14px;
        border: 2px solid rgba(255,255,255,.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin .6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    [x-cloak] { display: none !important; }

    /* utils */
    .d-flex { display: flex; }
    .flex-column { flex-direction: column; }
    .align-items-center { align-items: center; }
    .align-items-start { align-items: flex-start; }
    .justify-content-between { justify-content: space-between; }
    .text-center { text-align: center; }
    .ms-auto { margin-left: auto; }
    .gap-2 { gap: 8px; } .gap-3 { gap: 12px; }
    .mb-1 { margin-bottom: 4px; } .mb-2 { margin-bottom: 8px; }
    .mb-3 { margin-bottom: 12px; } .mb-4 { margin-bottom: 16px; }
    .py-4 { padding-top: 24px; padding-bottom: 24px; }
</style>
@endpush