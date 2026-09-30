{{-- resources/views/livewire/rider/finance-component.blade.php --}}
{{-- Styles: resources/css/blade.css (shared classes, Bootstrap 5 required) --}}
<div>

    {{-- ══════════════════════════════════════════════
         STATS ROW — 3 summary cards
         ══════════════════════════════════════════════ --}}
    <div class="stats-row mb-4">

        {{-- This Month's Earnings --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#dcfce7;">
                <span class="stat-icon">💰</span>
            </div>
            <div class="stat-body">
                <div class="stat-label">This Month's Earnings</div>
                <div class="stat-value" style="color:var(--success);">{{ $monthlyEarnings }}</div>
            </div>
        </div>

        {{-- Total Deliveries --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#fce7f3;">
                <span class="stat-icon">🛵</span>
            </div>
            <div class="stat-body">
                <div class="stat-label">Total Deliveries</div>
                <div class="stat-value" style="color:#db2777;">{{ $monthlyDeliveries }}</div>
            </div>
        </div>

        {{-- Sent to Bank --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#dbeafe;">
                <span class="stat-icon">🏦</span>
            </div>
            <div class="stat-body">
                <div class="stat-label">Sent to Bank</div>
                <div class="stat-value" style="color:#1d4ed8;">{{ $totalPayout }}</div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════
         PAYMENT HISTORY TABLE
         ══════════════════════════════════════════════ --}}
    <div class="card" style="padding:0;overflow:hidden;">

        <div style="padding:18px 20px 14px;border-bottom:1px solid var(--border);">
            <span style="font-size:14px;font-weight:800;color:var(--dark);">Payment History</span>
        </div>

        @forelse($rows as $row)
            <div
                class="payout-row"
                wire:key="payout-{{ $row['id'] }}"
            >
                <div class="payout-left">
                    <div style="font-size:14px;font-weight:700;color:var(--dark);">
                        {{ $row['date_label'] }}
                    </div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px;">
                        {{ $row['method'] }}
                    </div>
                </div>

                <div class="payout-right">
                    <span style="font-size:16px;font-weight:800;color:var(--success);">
                        {{ $row['amount'] }}
                    </span>

                    {{--
                        BUG FIX: the original only branched on 'paid' vs 'processing'
                        and dumped every other status (pending, approved, rejected,
                        failed) into a generic "Pending" badge — so a rejected or
                        failed payout looked identical to one still pending review.
                        Now every real status from RiderPayout gets its own badge.
                    --}}
                    @switch($row['status'])
                        @case('paid')
                            <span class="badge-kk" style="background:#dcfce7;color:#166534;">Paid</span>
                            @break
                        @case('processing')
                            <span class="badge-kk" style="background:#fef3c7;color:#92400e;">Processing</span>
                            @break
                        @case('approved')
                            <span class="badge-kk" style="background:#dbeafe;color:#1e40af;">Approved</span>
                            @break
                        @case('rejected')
                            <span class="badge-kk" style="background:#fee2e2;color:#991b1b;">Rejected</span>
                            @break
                        @case('failed')
                            <span class="badge-kk" style="background:#fee2e2;color:#991b1b;">Failed</span>
                            @break
                        @default
                            <span class="badge-kk" style="background:#f3f4f6;color:var(--muted);">Pending</span>
                    @endswitch
                </div>
            </div>
        @empty
            <div class="text-center py-4" style="color:var(--muted);">
                <div style="font-size:48px;" class="mb-2">💸</div>
                <div style="font-weight:700;color:var(--muted);">No payments yet</div>
                <div style="font-size:13px;margin-top:4px;">Payments will show up here once processed.</div>
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
