{{-- resources/views/livewire/rider/dashboard-component.blade.php --}}
{{-- Styles: resources/css/blade.css (shared classes, Bootstrap 5 required) --}}
<div>

    {{-- ══════════════════════════════════════════════
         STAT CARDS — 4 cards
         ══════════════════════════════════════════════ --}}
    <div class="stats-grid mb-4">

        {{-- Today's Deliveries --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#dcfce7;">
                <span>✅</span>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:var(--success);">{{ $todayDeliveries }}</div>
                <div class="stat-label">Today's Deliveries</div>
                @if($isBestPerformer)
                    <div class="best-badge">🏆 Top Performer!</div>
                @endif
            </div>
            <div class="stat-bg-circle"></div>
        </div>

        {{-- Today's Earnings --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#fce7f3;">
                <span>💳</span>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:#db2777;">{{ $todayEarnings }}</div>
                <div class="stat-label">Today's Earnings</div>
            </div>
            <div class="stat-bg-circle"></div>
        </div>

        {{-- My Rating --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#dbeafe;">
                <span>⭐</span>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:#1d4ed8;">{{ $avgRating }}★</div>
                <div class="stat-label">My Rating</div>
            </div>
            <div class="stat-bg-circle"></div>
        </div>

        {{-- Today's Distance --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#fef9c3;">
                <span>🛣️</span>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:#ca8a04;">{{ $todayDistance }} km</div>
                <div class="stat-label">Today's Distance</div>
            </div>
            <div class="stat-bg-circle"></div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════
         MAIN GRID — Ongoing Deliveries + Weekly Earnings
         ══════════════════════════════════════════════ --}}
    <div class="main-grid">

        {{-- ── LEFT: Ongoing Deliveries ── --}}
        <div class="card" style="padding:20px 22px;">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <span style="font-size:16px;font-weight:900;color:var(--dark);">Ongoing Deliveries</span>
                <span class="count-badge">{{ count($ongoingOrders) }}</span>
            </div>

            <div class="d-flex flex-column gap-3">
                @forelse($ongoingOrders as $order)
                    <div class="ongoing-card" wire:key="dash-{{ $order['id'] }}">

                        {{-- Top row --}}
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="order-num">#{{ $order['order_number'] }}</span>
                            <span class="badge-kk" style="background:#fef3c7;color:#92400e;">{{ $order['status_label'] }}</span>
                        </div>

                        {{-- Restaurant --}}
                        <div class="info-row mb-1">
                            <span class="info-icon">🍽️</span>
                            <span style="font-weight:700;font-size:13px;">{{ $order['restaurant'] }}</span>
                        </div>

                        {{-- Address --}}
                        <div class="info-row mb-3">
                            <span class="info-icon">📍</span>
                            <span style="font-size:13px;color:var(--muted);">{{ $order['address'] }}</span>
                        </div>

                        {{-- Action buttons --}}
                        <div class="d-flex gap-2">
                            <button
                                class="btn-kk"
                                style="background:var(--success);color:#fff;flex:1;"
                                wire:click="completeDelivery({{ $order['id'] }})"
                                wire:confirm="Confirm that this delivery has been completed?"
                                wire:loading.attr="disabled"
                                wire:target="completeDelivery({{ $order['id'] }})"
                            >
                                <span wire:loading.remove wire:target="completeDelivery({{ $order['id'] }})">
                                    ✔ Complete
                                </span>
                                <span wire:loading wire:target="completeDelivery({{ $order['id'] }})">
                                    <span class="spinner"></span>
                                </span>
                            </button>

                            @if($order['customer_phone'])
                                <a
                                    href="tel:{{ $order['customer_phone'] }}"
                                    class="btn-kk"
                                    style="background:#f3f4f6;color:var(--dark);"
                                >
                                    📞 Call
                                </a>
                            @endif
                        </div>

                    </div>
                @empty
                    <div class="text-center py-4" style="color:var(--muted);">
                        <div style="font-size:40px;" class="mb-2">🛵</div>
                        <div style="font-weight:700;color:var(--muted);">No ongoing deliveries</div>
                    </div>
                @endforelse
            </div>

        </div>

        {{-- ── RIGHT: This Week's Earnings ── --}}
        <div class="card" style="padding:20px 22px;">

            <div class="mb-3">
                <span style="font-size:16px;font-weight:900;color:var(--dark);">This Week's Earnings</span>
            </div>

            <div class="d-flex flex-column gap-3">
                @foreach($weeklyEarnings as $day)
                    @php
                        $pct = $maxEarning > 0 ? round(($day['amount'] / $maxEarning)) : 0;
                    @endphp
                    <div class="bar-row">
                        <span class="bar-label">{{ $day['label'] }}</span>
                        <div class="bar-track">
                            <div
                                class="bar-fill"
                                style="width:{{ $pct }}%;"
                            ></div>
                        </div>
                        <span class="bar-amount">Tk {{ $day['amount'] }}</span>
                    </div>
                @endforeach
            </div>

        </div>

    </div>

</div>
