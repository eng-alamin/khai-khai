<div>

    {{-- ══════════════════════════════════════════════
         STAT CARDS — ৪টি
         ══════════════════════════════════════════════ --}}
    <div class="stats-grid mb-4">

        {{-- আজকের ডেলিভারি --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#dcfce7;">
                <span>✅</span>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:var(--success);">{{ $todayDeliveries }}</div>
                <div class="stat-label">আজকের ডেলিভারি</div>
                @if($isBestPerformer)
                    <div class="best-badge">🏆 সেরা পারফরমার!</div>
                @endif
            </div>
            <div class="stat-bg-circle"></div>
        </div>

        {{-- আজকের আয় --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#fce7f3;">
                <span>💳</span>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:#db2777;">{{ $todayEarnings }}</div>
                <div class="stat-label">আজকের আয়</div>
            </div>
            <div class="stat-bg-circle"></div>
        </div>

        {{-- আমার রেটিং --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#dbeafe;">
                <span>⭐</span>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:#1d4ed8;">{{ $avgRating }}★</div>
                <div class="stat-label">আমার রেটিং</div>
            </div>
            <div class="stat-bg-circle"></div>
        </div>

        {{-- আজকের দূরত্ব --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#fef9c3;">
                <span>🛣️</span>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:#ca8a04;">{{ $todayDistance }} কিমি</div>
                <div class="stat-label">আজকের দূরত্ব</div>
            </div>
            <div class="stat-bg-circle"></div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════
         MAIN GRID — চলমান ডেলিভারি + সাপ্তাহিক আয়
         ══════════════════════════════════════════════ --}}
    <div class="main-grid">

        {{-- ── LEFT: চলমান ডেলিভারি ── --}}
        <div class="card" style="padding:20px 22px;">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <span style="font-size:16px;font-weight:900;color:var(--text-1);">চলমান ডেলিভারি</span>
                <span class="count-badge">{{ count($ongoingOrders) }}টি</span>
            </div>

            <div class="d-flex flex-column gap-3">
                @forelse($ongoingOrders as $order)
                    <div class="ongoing-card" wire:key="dash-{{ $order['id'] }}">

                        {{-- Top row --}}
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="order-num">#{{ $order['order_number'] }}</span>
                            <span class="badge-kk" style="background:#fef3c7;color:#92400e;">চলমান</span>
                        </div>

                        {{-- Restaurant --}}
                        <div class="info-row mb-1">
                            <span class="info-icon">🍽️</span>
                            <span style="font-weight:700;font-size:13px;">{{ $order['restaurant'] }}</span>
                        </div>

                        {{-- Address --}}
                        <div class="info-row mb-3">
                            <span class="info-icon">📍</span>
                            <span style="font-size:13px;color:var(--text-2);">{{ $order['address'] }}</span>
                        </div>

                        {{-- Action buttons --}}
                        <div class="d-flex gap-2">
                            <button
                                class="btn-kk"
                                style="background:var(--success);color:#fff;flex:1;"
                                wire:click="completeDelivery({{ $order['id'] }})"
                                wire:confirm="ডেলিভারি সম্পন্ন হয়েছে নিশ্চিত করুন?"
                                wire:loading.attr="disabled"
                                wire:target="completeDelivery({{ $order['id'] }})"
                            >
                                <span wire:loading.remove wire:target="completeDelivery({{ $order['id'] }})">
                                    ✔ সম্পন্ন
                                </span>
                                <span wire:loading wire:target="completeDelivery({{ $order['id'] }})">
                                    <span class="spinner"></span>
                                </span>
                            </button>

                            @if($order['customer_phone'])
                                <a
                                    href="tel:{{ $order['customer_phone'] }}"
                                    class="btn-kk"
                                    style="background:#f3f4f6;color:var(--text-1);"
                                >
                                    📞 কল
                                </a>
                            @endif
                        </div>

                    </div>
                @empty
                    <div class="text-center py-4" style="color:var(--text-3);">
                        <div style="font-size:40px;" class="mb-2">🛵</div>
                        <div style="font-weight:700;color:var(--text-2);">কোনো চলমান ডেলিভারি নেই</div>
                    </div>
                @endforelse
            </div>

        </div>

        {{-- ── RIGHT: এই সপ্তাহের আয় ── --}}
        <div class="card" style="padding:20px 22px;">

            <div class="mb-3">
                <span style="font-size:16px;font-weight:900;color:var(--text-1);">এই সপ্তাহের আয়</span>
            </div>

            <div class="d-flex flex-column gap-3">
                @foreach($weeklyEarnings as $day)
                    @php
                        $pct = $maxEarning > 0 ? round(($day['amount'] / $maxEarning) * 100) : 0;
                    @endphp
                    <div class="bar-row">
                        <span class="bar-label">{{ $day['label'] }}</span>
                        <div class="bar-track">
                            <div
                                class="bar-fill"
                                style="width:{{ $pct }}%;"
                            ></div>
                        </div>
                        <span class="bar-amount">৳{{ $day['amount'] }}</span>
                    </div>
                @endforeach
            </div>

        </div>

    </div>

</div>

@push('styles')
<style>
    :root {
        --success: #16a34a;
        --warning: #f59e0b;
        --danger:  #ef4444;
        --text-1:  #1f2937;
        --text-2:  #4b5563;
        --text-3:  #9ca3af;
        --border:  #e5e7eb;
        --radius:  14px;
        --shadow:  0 2px 10px rgba(0,0,0,.06);
    }

    /* ── Stat Cards ── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
    }
    @media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 500px) { .stats-grid { grid-template-columns: 1fr; } }

    .stat-card {
        background: #fff;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        padding: 18px 18px 16px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        position: relative;
        overflow: hidden;
    }

    .stat-icon-wrap {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .stat-body { flex: 1; z-index: 1; }

    .stat-value {
        font-size: 24px;
        font-weight: 900;
        line-height: 1.1;
        margin-bottom: 4px;
    }

    .stat-label {
        font-size: 12px;
        color: var(--text-3);
        font-weight: 600;
    }

    .best-badge {
        margin-top: 5px;
        font-size: 11px;
        font-weight: 700;
        color: var(--success);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .stat-bg-circle {
        position: absolute;
        right: -18px; bottom: -18px;
        width: 70px; height: 70px;
        border-radius: 50%;
        background: rgba(0,0,0,.04);
    }

    /* ── Main Grid ── */
    .main-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
    @media (max-width: 768px) { .main-grid { grid-template-columns: 1fr; } }

    /* ── Card ── */
    .card {
        background: #fff;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
    }

    /* ── Count badge ── */
    .count-badge {
        background: #fce7f3;
        color: #db2777;
        font-size: 12px;
        font-weight: 800;
        padding: 4px 11px;
        border-radius: 999px;
    }

    /* ── Ongoing delivery sub-card ── */
    .ongoing-card {
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 14px 16px;
    }

    .order-num {
        font-size: 15px;
        font-weight: 900;
        color: #7c3aed;
    }

    /* ── Info row ── */
    .info-row {
        display: flex; align-items: flex-start; gap: 7px;
        color: var(--text-2);
    }
    .info-icon { font-size: 14px; flex-shrink: 0; margin-top: 1px; }

    /* ── Buttons ── */
    .btn-kk {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 5px; padding: 9px 14px; border-radius: 10px;
        font-size: 13px; font-weight: 700; border: none;
        cursor: pointer; white-space: nowrap; text-decoration: none;
        transition: opacity .15s, transform .1s;
    }
    .btn-kk:hover  { opacity: .87; }
    .btn-kk:active { transform: scale(.97); }
    .btn-kk:disabled { opacity: .5; cursor: not-allowed; }

    /* ── Badge ── */
    .badge-kk {
        display: inline-flex; align-items: center;
        padding: 4px 11px; border-radius: 999px;
        font-size: 12px; font-weight: 700; white-space: nowrap;
    }

    /* ── Weekly bar chart ── */
    .bar-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .bar-label {
        width: 30px;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-2);
        flex-shrink: 0;
        text-align: right;
    }

    .bar-track {
        flex: 1;
        height: 10px;
        background: #f0f0f5;
        border-radius: 999px;
        overflow: hidden;
    }

    .bar-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #f472b6, #ec4899);
        transition: width .4s ease;
        min-width: 4px;
    }

    .bar-amount {
        width: 54px;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-1);
        text-align: right;
        flex-shrink: 0;
    }

    /* ── Spinner ── */
    .spinner {
        display: inline-block;
        width: 14px; height: 14px;
        border: 2px solid rgba(255,255,255,.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin .6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Utilities ── */
    .d-flex               { display: flex; }
    .flex-column          { flex-direction: column; }
    .align-items-center   { align-items: center; }
    .justify-content-between { justify-content: space-between; }
    .text-center          { text-align: center; }
    .gap-2 { gap: 8px; } .gap-3 { gap: 12px; }
    .mb-1 { margin-bottom: 4px; } .mb-2 { margin-bottom: 8px; }
    .mb-3 { margin-bottom: 12px; } .mb-4 { margin-bottom: 16px; }
    .py-4 { padding-top: 24px; padding-bottom: 24px; }
</style>
@endpush