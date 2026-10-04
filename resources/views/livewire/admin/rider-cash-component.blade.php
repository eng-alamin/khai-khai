{{-- resources/views/livewire/admin/rider-cash-component.blade.php --}}
<div>

    @if(session('success'))
        <div class="alert alert-success">
            <i class="material-icons-round">check_circle</i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        <div class="topbar">
            <div class="topbar-title">
                <span class="title-emoji">💵</span>
                Rider Cash (COD)
            </div>
        </div>

        {{-- ── Stats ── --}}
        <div class="mini-stats">
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(239,68,68,.12);color:#dc2626">
                    <span class="material-icons-round">account_balance_wallet</span>
                </div>
                <div>
                    <div class="mini-stat-value">৳{{ number_format($totals['in_hand']) }}</div>
                    <div class="mini-stat-label">Cash still with riders</div>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(34,197,94,.12);color:#16a34a">
                    <span class="material-icons-round">today</span>
                </div>
                <div>
                    <div class="mini-stat-value">৳{{ number_format($totals['deposited_today']) }}</div>
                    <div class="mini-stat-label">Received today</div>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(59,130,246,.12);color:#2563eb">
                    <span class="material-icons-round">calendar_month</span>
                </div>
                <div>
                    <div class="mini-stat-value">৳{{ number_format($totals['deposited_month']) }}</div>
                    <div class="mini-stat-label">Received this month</div>
                </div>
            </div>
        </div>

        @unless($netting)
            <div class="alert alert-error" style="margin-bottom:12px;">
                <i class="material-icons-round">info</i>
                <span>Cash netting is switched off (admin setting <b>rider_cash_netting</b> = 0). A rider's payout balance does not shrink by the cash he holds.</span>
            </div>
        @endunless

        {{-- ── Search + filter ── --}}
        <div class="filterbar">
            <div class="search-inner filterbar-search">
                <span class="material-icons-round search-icon">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search rider name or phone...">
            </div>
            <div class="filterbar-selects">
                <select class="select" wire:model.live="onlyHolding">
                    <option value="1">Only riders holding cash</option>
                    <option value="0">All riders</option>
                </select>
            </div>
        </div>

        {{-- ── Rider cards ── --}}
        @forelse($riders as $rider)
            @php $s = $summaries[$rider->id]; @endphp

            <div class="card" wire:key="cash-{{ $rider->id }}">
                <div class="card-top">
                    <div class="card-thumb">{{ strtoupper(substr($rider->name, 0, 1)) }}</div>
                    <div class="card-info">
                        <div class="card-title">{{ $rider->name }}</div>
                        <div class="card-desc">{{ $rider->phone }}</div>
                    </div>
                    <div class="badges-col">
                        @if($s['in_hand'] > 0)
                            <span class="status-badge unavailable">Holds ৳{{ number_format($s['in_hand'], 2) }}</span>
                        @else
                            <span class="status-badge available">Settled</span>
                        @endif
                    </div>
                </div>

                <div class="card-meta">
                    <span class="meta-item">COD collected ৳{{ number_format($s['collected'], 2) }}</span>
                    <span class="meta-item">Deposited ৳{{ number_format($s['deposited'], 2) }}</span>
                    <span class="meta-item">Earnings ৳{{ number_format($s['earned'], 2) }}</span>
                    <span class="cat-pill">Can withdraw ৳{{ number_format($s['available'], 2) }}</span>
                </div>

                <div class="card-bottom">
                    <div></div>
                    <div class="card-actions">
                        @if($s['in_hand'] > 0)
                            <button class="btn-approve" wire:click="openDeposit({{ $rider->id }})">
                                <span class="material-icons-round">add_card</span>
                                Record Deposit
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="empty">
                <span class="material-icons-round empty-icon">two_wheeler</span>
                <p>No riders found.</p>
            </div>
        @endforelse

        @if($riders->hasPages())
            <div class="pagination">
                <small>Showing {{ $riders->firstItem() ?? 0 }}–{{ $riders->lastItem() ?? 0 }} of {{ $riders->total() }} total</small>
                {{ $riders->links() }}
            </div>
        @endif

        {{-- ── Recent deposits ── --}}
        @if($recent->isNotEmpty())
            <div class="history-card" style="margin-top:18px;">
                <div class="history-title">Recent deposits</div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Rider</th><th>Amount</th><th>Method</th><th>Reference</th><th>Received by</th></tr>
                        </thead>
                        <tbody>
                            @foreach($recent as $d)
                                <tr wire:key="dep-{{ $d->id }}">
                                    <td>{{ $d->deposited_at->format('d M, h:i A') }}</td>
                                    <td>{{ $d->rider?->name }}</td>
                                    <td>৳{{ number_format((float) $d->amount, 2) }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $d->method)) }}</td>
                                    <td>{{ $d->reference ?? '—' }}</td>
                                    <td>{{ $d->recordedBy?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══ Deposit modal ══ --}}
    @if($showDeposit && $this->depositRider)
        @php $r = $this->depositRider; @endphp

        <div class="jara-modal-backdrop" wire:click.self="closeDeposit">
            <div class="jara-modal">
                <div class="jara-modal-drag"></div>

                <div class="jara-modal-header">
                    <div class="jara-modal-title">💵 Record cash deposit</div>
                    <button class="jara-modal-close" wire:click="closeDeposit">✕</button>
                </div>

                <div class="jara-modal-body">
                    <p style="margin:0 0 12px;">
                        <strong>{{ $r['name'] }}</strong> ({{ $r['phone'] }})<br>
                        Holds: <strong>৳{{ number_format(max(0, $r['in_hand']), 2) }}</strong>
                    </p>

                    <div class="form-group">
                        <label class="form-label">Amount received <span class="req">*</span></label>
                        <input type="number" step="0.01" min="0"
                            class="form-control @error('amount') is-invalid @enderror" wire:model="amount">
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Received by <span class="req">*</span></label>
                        <select class="form-control @error('method') is-invalid @enderror" wire:model.live="method">
                            <option value="cash">Cash (hand to hand)</option>
                            <option value="bkash">bKash</option>
                            <option value="bank_transfer">Bank transfer</option>
                        </select>
                        @error('method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Transaction ID / slip no.
                            @if($method !== 'cash') <span class="req">*</span> @else <small>(optional)</small> @endif
                        </label>
                        <input type="text" class="form-control @error('reference') is-invalid @enderror" wire:model="reference">
                        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Note <small>(optional)</small></label>
                        <input type="text" class="form-control @error('note') is-invalid @enderror" wire:model="note">
                        @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="jara-modal-footer">
                    <button class="btn-secondary" wire:click="closeDeposit">Cancel</button>
                    <button class="btn-primary" wire:click="saveDeposit" wire:loading.attr="disabled">
                        <span wire:loading wire:target="saveDeposit" class="spinner-sm"></span>
                        Save Deposit
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
