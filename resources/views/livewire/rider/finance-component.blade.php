<div>

    {{-- ══════════════════════════════════════════════
         STATS ROW — ৩টি সারাংশ কার্ড
         ══════════════════════════════════════════════ --}}
    <div class="stats-row mb-4">

        {{-- এই মাসের আয় --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#dcfce7;">
                <span class="stat-icon">💰</span>
            </div>
            <div class="stat-body">
                <div class="stat-label">এই মাসের আয়</div>
                <div class="stat-value" style="color:var(--success);">{{ $monthlyEarnings }}</div>
            </div>
        </div>

        {{-- মোট ডেলিভারি --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#fce7f3;">
                <span class="stat-icon">🛵</span>
            </div>
            <div class="stat-body">
                <div class="stat-label">মোট ডেলিভারি</div>
                <div class="stat-value" style="color:#db2777;">{{ $monthlyDeliveries }}</div>
            </div>
        </div>

        {{-- ব্যাংকে পাঠানো --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#dbeafe;">
                <span class="stat-icon">🏦</span>
            </div>
            <div class="stat-body">
                <div class="stat-label">ব্যাংকে পাঠানো</div>
                <div class="stat-value" style="color:#1d4ed8;">{{ $totalPayout }}</div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════
         PAYMENT HISTORY TABLE
         ══════════════════════════════════════════════ --}}
    <div class="card" style="padding:0;overflow:hidden;">

        <div style="padding:18px 20px 14px;border-bottom:1px solid var(--border);">
            <span style="font-size:14px;font-weight:800;color:var(--text-1);">পেমেন্ট ইতিহাস</span>
        </div>

        @forelse($rows as $row)
            <div
                class="payout-row"
                wire:key="payout-{{ $row['id'] }}"
            >
                <div class="payout-left">
                    <div style="font-size:14px;font-weight:700;color:var(--text-1);">
                        {{ $row['date_label'] }}
                    </div>
                    <div style="font-size:12px;color:var(--text-3);margin-top:2px;">
                        {{ $row['method'] }}
                    </div>
                </div>

                <div class="payout-right">
                    <span style="font-size:16px;font-weight:800;color:var(--success);">
                        {{ $row['amount'] }}
                    </span>

                    @if($row['status'] === 'paid')
                        <span class="badge-kk" style="background:#dcfce7;color:#166534;">পেইড</span>
                    @elseif($row['status'] === 'processing')
                        <span class="badge-kk" style="background:#fef3c7;color:#92400e;">প্রসেসিং</span>
                    @else
                        <span class="badge-kk" style="background:#f3f4f6;color:var(--text-3);">পেন্ডিং</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-4" style="color:var(--text-3);">
                <div style="font-size:48px;" class="mb-2">💸</div>
                <div style="font-weight:700;color:var(--text-2);">এখনো কোনো পেমেন্ট নেই</div>
                <div style="font-size:13px;margin-top:4px;">পেমেন্ট হলে এখানে দেখা যাবে।</div>
            </div>
        @endforelse

    </div>

    {{-- ── Pagination ── --}}
    @if($rows->hasPages())
        <div class="mt-3 d-flex justify-content-center">
            {{ $rows->links() }}
        </div>
    @endif

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

    /* ── Card ── */
    .card {
        background: #fff;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
    }

    /* ── Stats row ── */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }

    @media (max-width: 600px) {
        .stats-row { grid-template-columns: 1fr; }
    }

    .stat-card {
        background: #fff;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        padding: 16px 18px;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .stat-icon-wrap {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }

    .stat-icon { font-size: 22px; }

    .stat-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--text-3);
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 22px;
        font-weight: 900;
        line-height: 1;
    }

    /* ── Payout rows ── */
    .payout-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-bottom: 1px solid var(--border);
    }
    .payout-row:last-child { border-bottom: none; }

    .payout-left  { display: flex; flex-direction: column; }
    .payout-right { display: flex; align-items: center; gap: 10px; }

    /* ── Badge ── */
    .badge-kk {
        display: inline-flex; align-items: center;
        padding: 4px 11px; border-radius: 999px;
        font-size: 12px; font-weight: 700; white-space: nowrap;
    }

    /* ── Utilities ── */
    .d-flex               { display: flex; }
    .align-items-center   { align-items: center; }
    .justify-content-center { justify-content: center; }
    .text-center          { text-align: center; }
    .mb-2 { margin-bottom: 8px; }
    .mb-4 { margin-bottom: 16px; }
    .mt-3 { margin-top: 12px; }
    .py-4 { padding-top: 24px; padding-bottom: 24px; }
</style>
@endpush