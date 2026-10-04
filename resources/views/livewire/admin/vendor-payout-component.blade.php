{{-- resources/views/livewire/admin/vendor-payout-component.blade.php --}}
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
                <span class="title-emoji">🏪</span>
                Vendor Payouts
            </div>
            <button class="btn-new-adm" wire:click="openGenerate">
                <span class="plus-icon">＋</span>
                Generate Payouts
            </button>
        </div>

        {{-- ── Stats Row ── --}}
        <div class="mini-stats">
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(245,158,11,.12);color:#f59e0b">
                    <span class="material-icons-round">pending_actions</span>
                </div>
                <div>
                    <div class="mini-stat-value">{{ $stats['open_count'] }}</div>
                    <div class="mini-stat-label">To be paid (৳{{ number_format($stats['open_amount']) }})</div>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(59,130,246,.12);color:#2563eb">
                    <span class="material-icons-round">savings</span>
                </div>
                <div>
                    <div class="mini-stat-value">৳{{ number_format($stats['unsettled']) }}</div>
                    <div class="mini-stat-label">Earned, not in any payout yet</div>
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

        {{-- ── Search + Status ── --}}
        <div class="filterbar">
            <div class="search-inner filterbar-search">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search restaurant, payout number or reference...">
            </div>

            <div class="filterbar-selects">
                <select class="select" wire:model.live="statusFilter">
                    <option value="">All Status</option>
                    <option value="pending">⏳ Pending</option>
                    <option value="processing">🔄 Processing</option>
                    <option value="paid">💰 Paid</option>
                    <option value="failed">⚠️ Failed / Cancelled</option>
                </select>
            </div>
        </div>

        {{-- ── Payout Cards ── --}}
        @forelse($payouts as $payout)
            @php
                $restaurant = $payout->restaurant;
                $badge = match($payout->status) {
                    'paid'   => 'available',
                    'failed' => 'unavailable',
                    default  => 'pending',
                };
            @endphp

            <div class="card" wire:key="vpayout-{{ $payout->id }}">
                <div class="card-top">
                    <div class="card-thumb">
                        {{ strtoupper(substr($restaurant?->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="card-info">
                        <div class="card-title">{{ $restaurant?->name ?? 'Deleted restaurant' }}</div>
                        <div class="card-desc">
                            {{ $payout->payout_number }}
                            · {{ $payout->period_start->format('d M') }} – {{ $payout->period_end->format('d M Y') }}
                            @if($restaurant?->owner) · {{ $restaurant->owner->name }} @endif
                            @if($restaurant?->phone) · {{ $restaurant->phone }} @endif
                        </div>
                    </div>
                    <div class="badges-col">
                        <span class="status-badge {{ $badge }}">{{ ucfirst($payout->status) }}</span>
                    </div>
                </div>

                <div class="card-meta">
                    <span class="cat-pill">Pay ৳{{ number_format((float) $payout->net_amount, 2) }}</span>
                    <span class="meta-item">
                        <span class="material-icons-round">receipt</span>
                        {{ $payout->orders_count }} orders · sales ৳{{ number_format((float) $payout->gross_subtotal) }}
                    </span>
                    <span class="meta-item">
                        <span class="material-icons-round">percent</span>
                        commission ৳{{ number_format((float) $payout->commission_amount) }} ({{ rtrim(rtrim(number_format((float) $payout->commission_rate, 2), '0'), '.') }}%)
                    </span>
                    @if((float) $payout->adjustment_amount !== 0.0)
                        <span class="meta-item" style="color:#dc2626;">
                            <span class="material-icons-round">currency_exchange</span>
                            refunds −৳{{ number_format(abs((float) $payout->adjustment_amount), 2) }}
                        </span>
                    @endif
                    @if($payout->transaction_reference)
                        <span class="meta-item">
                            <span class="material-icons-round">receipt_long</span>
                            {{ strtoupper(str_replace('_', ' ', (string) $payout->payment_method)) }} · {{ $payout->transaction_reference }}
                        </span>
                    @endif
                </div>

                @if(in_array($payout->status, ['pending', 'processing'], true))
                    @php $ownerAccounts = $accounts->get($restaurant?->owner_id, collect()); @endphp
                    <div style="font-size:12px;margin:6px 0;">
                        @if($ownerAccounts->isNotEmpty())
                            <strong>Send to:</strong>
                            @foreach($ownerAccounts as $acc)
                                <span class="cat-pill" wire:key="acc-{{ $acc->id }}">{{ $acc->label }}</span>
                            @endforeach
                        @else
                            <span style="color:#b45309;">⚠️ The vendor has not saved a payout account yet.</span>
                        @endif
                    </div>
                @endif

                @if($payout->failure_reason)
                    <div style="font-size:12px;opacity:.75;margin:6px 0;">Reason: {{ $payout->failure_reason }}</div>
                @endif

                <div class="card-bottom">
                    <div></div>
                    <div class="card-actions">
                        @if(in_array($payout->status, ['pending', 'processing'], true))
                            <button class="btn-approve" wire:click="openAction({{ $payout->id }}, 'paid')">
                                <span class="material-icons-round">paid</span>
                                Mark Paid
                            </button>
                            <button class="btn-block" wire:click="openAction({{ $payout->id }}, 'failed')" title="Cancel / failed">
                                <span class="material-icons-round">block</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="empty">
                <span class="material-icons-round empty-icon">storefront</span>
                <p>No vendor payouts found.</p>
                <button class="btn-new-adm" wire:click="openGenerate">+ Generate Payouts</button>
            </div>
        @endforelse

        @if($payouts->hasPages())
            <div class="pagination">
                <small>Showing {{ $payouts->firstItem() ?? 0 }}–{{ $payouts->lastItem() ?? 0 }} of {{ $payouts->total() }} total</small>
                {{ $payouts->links() }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Generate Modal
    ══════════════════════════════════════ --}}
    @if($showGenerate)
        @php $preview = $this->preview; @endphp

        <div class="jara-modal-backdrop" wire:click.self="closeGenerate">
            <div class="jara-modal">
                <div class="jara-modal-drag"></div>

                <div class="jara-modal-header">
                    <div class="jara-modal-title">🏪 Generate Vendor Payouts</div>
                    <button class="jara-modal-close" wire:click="closeGenerate">✕</button>
                </div>

                <div class="jara-modal-body">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">From <span class="req">*</span></label>
                                <input type="date" class="form-control @error('periodStart') is-invalid @enderror"
                                    wire:model.live="periodStart">
                                @error('periodStart') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">To <span class="req">*</span></label>
                                <input type="date" class="form-control @error('periodEnd') is-invalid @enderror"
                                    wire:model.live="periodEnd" max="{{ now()->subDay()->toDateString() }}">
                                @error('periodEnd') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Restaurant</label>
                        <select class="form-control" wire:model.live="genRestaurantId">
                            <option value="">All restaurants</option>
                            @foreach($restaurants as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <hr>

                    @if($preview === null)
                        <p style="margin:0;opacity:.7;">Choose a valid period (it must end yesterday or earlier).</p>
                    @elseif(count($preview['rows']) === 0)
                        <p style="margin:0;opacity:.7;">Nothing to settle in this period.</p>
                    @else
                        <div style="font-weight:700;margin-bottom:8px;">
                            {{ count($preview['rows']) }} payout(s) · {{ $preview['orders'] }} orders · ৳{{ number_format($preview['total_net'], 2) }}
                        </div>
                        @foreach($preview['rows'] as $row)
                            <div style="display:flex;justify-content:space-between;gap:10px;padding:6px 0;" wire:key="prev-{{ $row['restaurant_id'] }}">
                                <span>
                                    {{ $row['name'] }} <small style="opacity:.7;">· {{ $row['orders'] }} orders</small>
                                    @if(($row['refund_deduction'] ?? 0) > 0)
                                        <br><small style="color:#dc2626;">includes refunds −৳{{ number_format($row['refund_deduction'], 2) }}</small>
                                    @endif
                                </span>
                                <strong>৳{{ number_format($row['net'], 2) }}</strong>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="jara-modal-footer">
                    <button class="btn-secondary" wire:click="closeGenerate">Cancel</button>
                    <button class="btn-primary" wire:click="generate" wire:loading.attr="disabled"
                        @disabled($preview === null || count($preview['rows']) === 0)>
                        <span wire:loading wire:target="generate" class="spinner-sm"></span>
                        Create Payouts
                    </button>
                </div>
            </div>
        </div>
    @endif


    {{-- ══════════════════════════════════════
         Action Modal (paid / failed)
    ══════════════════════════════════════ --}}
    @if($showAction && $this->actionPayout)
        @php $p = $this->actionPayout; @endphp

        <div class="jara-modal-backdrop" wire:click.self="closeAction">
            <div class="jara-modal">
                <div class="jara-modal-drag"></div>

                <div class="jara-modal-header">
                    <div class="jara-modal-title">{{ $actionType === 'paid' ? '💰 Mark as paid' : '⚠️ Cancel / failed' }}</div>
                    <button class="jara-modal-close" wire:click="closeAction">✕</button>
                </div>

                <div class="jara-modal-body">
                    <p style="margin:0 0 12px;">
                        <strong>{{ $p->restaurant?->name }}</strong><br>
                        {{ $p->payout_number }} · {{ $p->orders_count }} orders<br>
                        Amount: <strong>৳{{ number_format((float) $p->net_amount, 2) }}</strong>
                        @if((float) $p->adjustment_amount !== 0.0)
                            <br><small style="color:#dc2626;">after refunds −৳{{ number_format(abs((float) $p->adjustment_amount), 2) }}</small>
                        @endif
                    </p>

                    @if($actionType === 'paid')
                        @php $modalAccounts = $accounts->get($p->restaurant?->owner_id, collect()); @endphp
                        @if($modalAccounts->isNotEmpty())
                            <div style="font-size:12px;margin-bottom:10px;">
                                <strong>Send to:</strong>
                                @foreach($modalAccounts as $acc)
                                    <div>{{ $acc->label }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="form-group">
                            <label class="form-label">Paid by <span class="req">*</span></label>
                            <select class="form-control @error('method') is-invalid @enderror" wire:model.live="method">
                                <option value="bkash">bKash</option>
                                <option value="nagad">Nagad</option>
                                <option value="bank_transfer">Bank transfer</option>
                                <option value="cash">Cash</option>
                            </select>
                            @error('method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Transaction ID / reference
                                @if($method !== 'cash') <span class="req">*</span> @else <small>(optional)</small> @endif
                            </label>
                            <input type="text" class="form-control @error('reference') is-invalid @enderror"
                                wire:model="reference" placeholder="e.g. bKash TrxID">
                            @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <div class="form-group">
                            <label class="form-label">Reason <span class="req">*</span></label>
                            <textarea rows="3" class="form-control @error('reason') is-invalid @enderror"
                                wire:model="reason" placeholder="Why is this payout cancelled?"></textarea>
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-hint">The orders go back to "not in any payout" and can be included in a new payout.</div>
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
