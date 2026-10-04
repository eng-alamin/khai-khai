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

        {{-- Available Balance --}}
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:#fef3c7;">
                <span class="stat-icon">👛</span>
            </div>
            <div class="stat-body">
                <div class="stat-label">Available Balance</div>
                <div class="stat-value" style="color:#b45309;">{{ $availableBalance }}</div>
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
         REQUEST A PAYOUT
         ══════════════════════════════════════════════ --}}
    <div class="card mb-4" style="padding:18px 20px;">
        <div style="font-size:14px;font-weight:800;color:var(--dark);margin-bottom:4px;">Request a Payout</div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:14px;">
            Minimum {{ $minPayout }}. Only one request can be in progress at a time.
        </div>

        @if($cashNetting && $hasCashInHand)
            <div style="font-size:13px;color:#991b1b;background:#fee2e2;border-radius:10px;padding:10px 12px;margin-bottom:14px;">
                💵 You are still holding <strong>{{ $cashInHand }}</strong> from Cash on Delivery orders.
                Hand it over to KhaiKhai; your Available Balance is reduced by this amount until you do.
            </div>
        @endif

        @if($hasOpenRequest)
            <div style="font-size:13px;color:#92400e;background:#fef3c7;border-radius:10px;padding:10px 12px;">
                You already have a payout request in progress. You can send a new one when it is finished.
            </div>
        @else
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" style="font-size:12px;font-weight:700;">Amount (Tk)</label>
                    <input type="number" step="0.01" min="0"
                        class="form-control-kk @error('payoutAmount') is-invalid @enderror"
                        wire:model="payoutAmount" placeholder="e.g. 1000">
                    @error('payoutAmount') <div style="color:#dc2626;font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label" style="font-size:12px;font-weight:700;">Method</label>
                    <select class="form-control-kk" wire:model.live="payoutMethod">
                        <option value="bkash">bKash</option>
                        <option value="bank">Bank</option>
                    </select>
                    @error('payoutMethod') <div style="color:#dc2626;font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-5">
                    <label class="form-label" style="font-size:12px;font-weight:700;">
                        {{ $payoutMethod === 'bkash' ? 'bKash number' : 'Bank account number' }}
                    </label>
                    <input type="text"
                        class="form-control-kk @error('payoutAccount') is-invalid @enderror"
                        wire:model="payoutAccount"
                        placeholder="{{ $payoutMethod === 'bkash' ? '01XXXXXXXXX' : 'Account number' }}">
                    @error('payoutAccount') <div style="color:#dc2626;font-size:12px;margin-top:4px;">{{ $message }}</div> @enderror
                </div>
            </div>

            <div style="margin-top:14px;">
                <button type="button" class="btn-kk btn-primary-kk" wire:click="requestPayout" wire:loading.attr="disabled">
                    <span wire:loading wire:target="requestPayout">Sending...</span>
                    <span wire:loading.remove wire:target="requestPayout">Send Request</span>
                </button>
            </div>
        @endif
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
