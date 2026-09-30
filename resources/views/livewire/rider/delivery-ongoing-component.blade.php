{{-- resources/views/livewire/rider/delivery-ongoing-component.blade.php --}}
{{-- Styles: resources/css/blade.css (shared classes, Bootstrap 5 required) --}}
<div>

    {{-- ══════════════════════════════════════════════
         HEADER — Online status + today's earnings
         ══════════════════════════════════════════════ --}}
    <div class="card mb-4" style="padding:16px 20px;">
        <div class="d-flex align-items-center justify-content-between">

            {{-- Today's earnings --}}
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:4px;">
                    Today's Earnings
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span style="font-size:22px;font-weight:900;color:var(--success);">
                        {{ $todayEarnings['total_taka'] }}
                    </span>
                    <span class="badge-kk" style="background:#dcfce7;color:#166534;">
                        {{ $todayEarnings['deliveries'] }} deliveries
                    </span>
                </div>
            </div>

            {{-- Online toggle --}}
            <button
                type="button"
                wire:click="toggleOnline"
                class="d-flex align-items-center gap-2"
                style="background:none;border:none;cursor:pointer;padding:8px 14px;border-radius:999px;border:1.5px solid {{ $isOnline ? '#16a34a' : '#d1d5db' }};transition:all .2s;"
            >
                <span style="width:9px;height:9px;border-radius:50%;background:{{ $isOnline ? 'var(--success)' : '#9ca3af' }};display:inline-block;"></span>
                <span style="font-size:13px;color:{{ $isOnline ? 'var(--success)' : 'var(--muted)' }};font-weight:700;">
                    {{ $isOnline ? 'Online' : 'Offline' }}
                </span>
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         ONGOING — My active deliveries
         ══════════════════════════════════════════════ --}}
    <div class="mb-2" style="font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);">
        🛵 Ongoing Deliveries
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
                            📞 Call
                        </a>
                    @endif
                </div>

                {{-- Customer info --}}
                <div class="info-row mb-1">
                    <span class="info-icon">👤</span>
                    <span>{{ $order['customer'] }}</span>
                    @if($order['customer_phone'])
                        <a href="tel:{{ $order['customer_phone'] }}" class="call-btn ms-auto">
                            📞 Call
                        </a>
                    @endif
                </div>

                {{-- Address --}}
                <div class="info-row mb-3">
                    <span class="info-icon">📍</span>
                    <span style="color:var(--muted);">{{ $order['address'] }}</span>
                </div>

                {{-- Items --}}
                <div class="items-box mb-3">
                    <span style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;">
                        Items
                    </span>
                    <div style="font-size:13px;color:var(--muted);margin-top:4px;">{{ $order['items'] }}</div>
                </div>

                {{-- Route Map (collapsible) --}}
                <div class="mb-3">
                    <button
                        type="button"
                        @click="showMap = !showMap"
                        class="btn-kk"
                        style="background:#eff6ff;color:#1d4ed8;width:100%;justify-content:center;"
                    >
                        <span x-show="!showMap">🗺️ View Route</span>
                        <span x-show="showMap" style="display:none;">🔼 Hide Route</span>
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
                        <div style="font-size:11px;color:var(--muted);">
                            Your earning: <strong style="color:var(--success);">{{ $order['delivery_fee'] }}</strong>
                        </div>
                    </div>

                    <div class="d-flex gap-2 align-items-center">
                        {{-- Payment badge --}}
                        @if($order['payment_method'] === 'cash_on_delivery')
                            <span class="badge-kk" style="background:#fef3c7;color:#92400e;font-size:11px;">
                                💵 Collect Cash
                            </span>
                        @else
                            <span class="badge-kk" style="background:#dcfce7;color:#166534;font-size:11px;">
                                ✅ Paid
                            </span>
                        @endif

                        <button
                            class="btn-kk"
                            style="background:var(--success);color:#fff;"
                            wire:click="completeDelivery({{ $order['id'] }})"
                            wire:confirm="Confirm that this delivery has been completed?"
                            wire:loading.attr="disabled"
                            wire:target="completeDelivery({{ $order['id'] }})"
                        >
                            <span wire:loading.remove wire:target="completeDelivery({{ $order['id'] }})">
                                ✅ Delivered
                            </span>
                            <span wire:loading wire:target="completeDelivery({{ $order['id'] }})">
                                <span class="spinner"></span>
                            </span>
                        </button>
                    </div>
                </div>

            </div>
        @empty
            <div class="card text-center py-4" style="color:var(--muted);">
                <div style="font-size:48px;" class="mb-2">🛵</div>
                <div style="font-weight:700;color:var(--muted);">No ongoing deliveries right now</div>
            </div>
        @endforelse
    </div>

    {{-- ══════════════════════════════════════════════
         AVAILABLE — Orders waiting for pickup
         ══════════════════════════════════════════════ --}}
    <div class="d-flex align-items-center gap-2 mb-2">
        <span style="font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);">
            📦 New Orders
        </span>
        @if(!$isOnline)
            <span class="badge-kk" style="background:#f3f4f6;color:var(--muted);font-size:11px;">
                Visible when you're online
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
                        <span style="font-size:12px;color:var(--muted);">
                            ⏱ {{ $order['time_label'] }}
                        </span>
                        <span class="badge-kk" style="background:#fef3c7;color:#92400e;">
                            Pickup Pending
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
                    <span style="color:var(--muted);">{{ $order['address'] }}</span>
                </div>

                {{-- Items summary --}}
                <div class="items-box mb-3">
                    <span style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;">
                        Items
                    </span>
                    <div style="font-size:13px;color:var(--muted);margin-top:4px;">{{ $order['items'] }}</div>
                </div>

                {{-- Footer --}}
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div style="font-size:20px;font-weight:900;">{{ $order['total'] }}</div>
                        <div style="font-size:11px;color:var(--muted);">
                            Delivery earning: <strong style="color:var(--success);">{{ $order['delivery_fee'] }}</strong>
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
                            🛵 Accept
                        </span>
                        <span wire:loading wire:target="acceptDelivery({{ $order['id'] }})">
                            <span class="spinner"></span>
                        </span>
                    </button>
                </div>

            </div>
        @empty
            @if($isOnline)
                <div class="card text-center py-4" style="color:var(--muted);">
                    <div style="font-size:48px;" class="mb-2">🎉</div>
                    <div style="font-weight:700;color:var(--muted);">No orders waiting right now</div>
                    <div style="font-size:13px;margin-top:4px;">New orders will show up here as they arrive.</div>
                </div>
            @else
                <div class="card text-center py-4" style="color:var(--muted);">
                    <div style="font-size:48px;" class="mb-2">😴</div>
                    <div style="font-weight:700;color:var(--muted);">You're currently offline</div>
                    <div style="font-size:13px;margin-top:4px;">Tap the online button above to start receiving orders.</div>
                </div>
            @endif
        @endforelse
    </div>

</div>
