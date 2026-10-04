{{-- resources/views/livewire/admin/rider-payout-component.blade.php --}}
<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="material-icons-round">check_circle</i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">
            <i class="material-icons-round">error</i>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="topbar">
            <div class="topbar-title">
                <span class="title-emoji">💸</span>
                Rider Payouts
            </div>
        </div>

        {{-- ── Stats Row ── --}}
        <div class="mini-stats">
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(245,158,11,.12);color:#f59e0b">
                    <span class="material-icons-round">hourglass_empty</span>
                </div>
                <div>
                    <div class="mini-stat-value">{{ $stats['pending_count'] }}</div>
                    <div class="mini-stat-label">Waiting for review (৳{{ number_format($stats['pending_amount']) }})</div>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(59,130,246,.12);color:#2563eb">
                    <span class="material-icons-round">account_balance_wallet</span>
                </div>
                <div>
                    <div class="mini-stat-value">{{ $stats['approved_count'] }}</div>
                    <div class="mini-stat-label">Approved, to be paid (৳{{ number_format($stats['approved_amount']) }})</div>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(34,197,94,.12);color:#16a34a">
                    <span class="material-icons-round">paid</span>
                </div>
                <div>
                    <div class="mini-stat-value">৳{{ number_format($stats['paid_month']) }}</div>
                    <div class="mini-stat-label">Paid this month</div>
                </div>
            </div>
        </div>

        {{-- ── Search (left) + Status Select (right) ── --}}
        <div class="filterbar">
            <div class="search-inner filterbar-search">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search rider name, phone, account or transaction ID...">
            </div>

            <div class="filterbar-selects">
                <select class="select" wire:model.live="statusFilter">
                    <option value="">All Status</option>
                    <option value="pending">⏳ Pending</option>
                    <option value="approved">✅ Approved</option>
                    <option value="processing">🔄 Processing</option>
                    <option value="paid">💰 Paid</option>
                    <option value="rejected">🚫 Rejected</option>
                    <option value="failed">⚠️ Failed</option>
                </select>
            </div>
        </div>

        {{-- ── Payout Cards ── --}}
        @forelse($payouts as $payout)
            @php
                $rider  = $payout->rider;
                $badge  = match($payout->status) {
                    'paid'                => 'available',
                    'rejected', 'failed'  => 'unavailable',
                    default               => 'pending',
                };
            @endphp

            <div class="card" wire:key="payout-{{ $payout->id }}">
                {{-- Top Row --}}
                <div class="card-top">
                    <div class="card-thumb">
                        {{ strtoupper(substr($rider?->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="card-info">
                        <div class="card-title">{{ $rider?->name ?? 'Deleted rider' }}</div>
                        <div class="card-desc">
                            {{ $rider?->phone }} · Request #{{ $payout->id }}
                            · {{ $payout->created_at->format('d M Y, h:i A') }}
                        </div>
                    </div>
                    <div class="badges-col">
                        <span class="status-badge {{ $badge }}">{{ ucfirst($payout->status) }}</span>
                    </div>
                </div>

                {{-- Meta --}}
                <div class="card-meta">
                    <span class="cat-pill">৳{{ number_format((float) $payout->amount, 2) }}</span>
                    <span class="meta-item">
                        <span class="material-icons-round">account_balance</span>
                        {{ $payout->method === 'bkash' ? 'bKash' : 'Bank' }} · {{ $payout->account_number }}
                    </span>
                    @if($payout->transaction_id)
                        <span class="meta-item">
                            <span class="material-icons-round">receipt_long</span>
                            {{ $payout->transaction_id }}
                        </span>
                    @endif
                </div>

                @if($payout->admin_note)
                    <div style="font-size:12px;opacity:.75;margin:6px 0;">Note: {{ $payout->admin_note }}</div>
                @endif

                {{-- Bottom Row --}}
                <div class="card-bottom">
                    <div></div>
                    <div class="card-actions">
                        @if($payout->status === 'pending')
                            <button class="btn-approve" wire:click="openAction({{ $payout->id }}, 'approve')">
                                <span class="material-icons-round">check_circle_outline</span>
                                Approve
                            </button>
                        @endif

                        @if(in_array($payout->status, ['approved', 'processing'], true))
                            <button class="btn-approve" wire:click="openAction({{ $payout->id }}, 'paid')">
                                <span class="material-icons-round">paid</span>
                                Mark Paid
                            </button>
                            <button class="btn-block" wire:click="openAction({{ $payout->id }}, 'failed')" title="Transfer failed">
                                <span class="material-icons-round">error_outline</span>
                            </button>
                        @endif

                        @if(in_array($payout->status, ['pending', 'approved'], true))
                            <button class="btn-block" wire:click="openAction({{ $payout->id }}, 'reject')" title="Reject">
                                <span class="material-icons-round">block</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="empty">
                <span class="material-icons-round empty-icon">payments</span>
                <p>No payout requests found.</p>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($payouts->hasPages())
            <div class="pagination">
                <small>Showing {{ $payouts->firstItem() ?? 0 }}–{{ $payouts->lastItem() ?? 0 }} of {{ $payouts->total() }} total</small>
                {{ $payouts->links() }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Action Modal (approve / reject / paid / failed)
    ══════════════════════════════════════ --}}
    @if($showAction && $this->actionPayout)
        @php
            $p = $this->actionPayout;
            $titles = [
                'approve' => '✅ Approve payout',
                'reject'  => '🚫 Reject payout',
                'paid'    => '💰 Mark as paid',
                'failed'  => '⚠️ Transfer failed',
            ];
        @endphp

        <div class="jara-modal-backdrop" wire:click.self="closeAction">
            <div class="jara-modal">
                <div class="jara-modal-drag"></div>

                <div class="jara-modal-header">
                    <div class="jara-modal-title">{{ $titles[$actionType] ?? 'Payout' }}</div>
                    <button class="jara-modal-close" wire:click="closeAction">✕</button>
                </div>

                <div class="jara-modal-body">
                    <p style="margin:0 0 12px;">
                        <strong>{{ $p->rider?->name }}</strong> asked for
                        <strong>৳{{ number_format((float) $p->amount, 2) }}</strong><br>
                        {{ $p->method === 'bkash' ? 'bKash' : 'Bank' }}: <strong>{{ $p->account_number }}</strong>
                    </p>

                    @if($actionType === 'paid')
                        <div class="form-group">
                            <label class="form-label">Transaction ID <span class="req">*</span></label>
                            <input type="text"
                                class="form-control @error('transactionId') is-invalid @enderror"
                                wire:model="transactionId"
                                placeholder="e.g. bKash TrxID">
                            @error('transactionId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="form-label">
                            @if(in_array($actionType, ['reject', 'failed'], true))
                                Reason <span class="req">*</span>
                            @else
                                Note <small>(optional)</small>
                            @endif
                        </label>
                        <textarea rows="3"
                            class="form-control @error('note') is-invalid @enderror"
                            wire:model="note"
                            placeholder="{{ in_array($actionType, ['reject', 'failed'], true) ? 'The rider will see this reason' : 'Internal note' }}"></textarea>
                        @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if(in_array($actionType, ['reject', 'failed'], true))
                        <div class="form-hint">The amount goes back to the rider's available balance.</div>
                    @endif
                </div>

                <div class="jara-modal-footer">
                    <button class="btn-secondary" wire:click="closeAction">Cancel</button>
                    <button class="btn-primary" wire:click="submitAction" wire:loading.attr="disabled">
                        <span wire:loading wire:target="submitAction" class="spinner-sm"></span>
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
