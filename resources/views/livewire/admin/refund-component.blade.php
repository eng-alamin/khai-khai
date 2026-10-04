{{-- resources/views/livewire/admin/refund-component.blade.php --}}
<div>

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

    @php
        $statusLabels = ['pending' => 'To send', 'paid' => 'Paid', 'cancelled' => 'Cancelled'];
        $badgeOf = fn ($s) => match ($s) {
            'paid'      => 'available',
            'cancelled' => 'unavailable',
            default     => 'pending',
        };
        $methodLabels = ['bkash' => 'bKash', 'nagad' => 'Nagad', 'bank_transfer' => 'Bank transfer', 'cash' => 'Cash'];
    @endphp

    <div class="main-content">

        <div class="topbar">
            <div class="topbar-title">
                <span class="title-emoji">💸</span>
                Refunds
            </div>
            <button class="btn-primary" wire:click="openCreate">
                <span class="material-icons-round">add</span> New refund
            </button>
        </div>

        <div class="mini-stats">
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(245,158,11,.12);color:#d97706">
                    <span class="material-icons-round">hourglass_top</span>
                </div>
                <div>
                    <div class="mini-stat-value">{{ (int) ($counts['pending'] ?? 0) }}</div>
                    <div class="mini-stat-label">To send</div>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(34,197,94,.12);color:#16a34a">
                    <span class="material-icons-round">task_alt</span>
                </div>
                <div>
                    <div class="mini-stat-value">{{ (int) ($counts['paid'] ?? 0) }}</div>
                    <div class="mini-stat-label">Paid</div>
                </div>
            </div>
        </div>

        <div class="filterbar">
            <div class="search-inner filterbar-search">
                <span class="material-icons-round search-icon">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search refund no., order no., customer name or phone...">
            </div>
            <div class="filterbar-selects">
                <select class="select" wire:model.live="statusFilter">
                    <option value="">All refunds</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @forelse($refunds as $refund)
            <div class="card" wire:key="refund-{{ $refund->id }}">
                <div class="card-top">
                    <div class="card-thumb">৳</div>
                    <div class="card-info">
                        <div class="card-title">৳{{ number_format((float) $refund->amount, 2) }} · Order #{{ $refund->order?->order_number }}</div>
                        <div class="card-desc">
                            {{ $refund->refund_number }} · {{ $refund->customer?->name ?? 'Deleted customer' }}
                            @if($refund->customer?->phone) · {{ $refund->customer->phone }} @endif
                            · {{ $refund->created_at->format('d M Y, h:i A') }}
                        </div>
                    </div>
                    <div class="badges-col">
                        <span class="status-badge {{ $badgeOf($refund->status) }}">{{ $statusLabels[$refund->status] ?? $refund->status }}</span>
                    </div>
                </div>

                <div class="card-meta">
                    <span class="cat-pill">{{ \App\Models\Refund::REASONS[$refund->reason] ?? $refund->reason }}</span>
                    <span class="meta-item">
                        @if($refund->bearer === 'vendor')
                            Restaurant pays ৳{{ number_format((float) $refund->vendor_deduction, 2) }} ({{ $refund->restaurant?->name }})
                            @if($refund->payout_id) · taken off a payout @elseif($refund->status === 'paid') · waits for next payout @endif
                        @else
                            Platform pays
                        @endif
                    </span>
                    @if($refund->status === 'paid')
                        <span class="meta-item">{{ $methodLabels[$refund->method] ?? $refund->method }}@if($refund->reference) · {{ $refund->reference }} @endif</span>
                    @endif
                    @if($refund->note)
                        <span class="meta-item">{{ \Illuminate\Support\Str::limit($refund->note, 90) }}</span>
                    @endif
                </div>

                @if($refund->status === 'pending')
                    <div class="card-bottom">
                        <div></div>
                        <div class="card-actions">
                            <button class="btn-approve" wire:click="openAction({{ $refund->id }}, 'pay')">
                                <span class="material-icons-round">send</span> Mark as paid
                            </button>
                            <button class="btn-reject" wire:click="openAction({{ $refund->id }}, 'cancel')">
                                <span class="material-icons-round">close</span> Cancel
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="empty">
                <span class="material-icons-round empty-icon">payments</span>
                <p>No refunds found.</p>
            </div>
        @endforelse

        @if($refunds->hasPages())
            <div class="pagination">
                <small>Showing {{ $refunds->firstItem() ?? 0 }}–{{ $refunds->lastItem() ?? 0 }} of {{ $refunds->total() }} total</small>
                {{ $refunds->links() }}
            </div>
        @endif

    </div>

    {{-- ══ New refund modal ══ --}}
    @if($showCreate)
        <div class="jara-modal-backdrop" wire:click.self="closeCreate">
            <div class="jara-modal">
                <div class="jara-modal-drag"></div>
                <div class="jara-modal-header">
                    <div class="jara-modal-title">💸 New refund</div>
                    <button class="jara-modal-close" wire:click="closeCreate">✕</button>
                </div>

                <div class="jara-modal-body">
                    <p style="margin:0 0 12px;opacity:.75;font-size:13px;">Only delivered orders whose cash was collected can be refunded. You send the money yourself, then mark the refund as paid.</p>

                    <div class="form-group">
                        <label class="form-label">Order number <span class="req">*</span></label>
                        <input type="text" class="form-control @error('orderNumber') is-invalid @enderror" wire:model="orderNumber" placeholder="KK...">
                        @error('orderNumber') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Refund amount (৳) <span class="req">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control @error('amount') is-invalid @enderror" wire:model="amount">
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Reason <span class="req">*</span></label>
                        <select class="form-control @error('reason') is-invalid @enderror" wire:model="reason">
                            @foreach(\App\Models\Refund::REASONS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Who carries the cost? <span class="req">*</span></label>
                        <select class="form-control @error('bearer') is-invalid @enderror" wire:model="bearer">
                            <option value="vendor">Restaurant (its share is taken off the next payout)</option>
                            <option value="platform">Platform (KhaiKhai pays)</option>
                        </select>
                        @error('bearer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Support ticket number (optional)</label>
                        <input type="text" class="form-control @error('ticketNumber') is-invalid @enderror" wire:model="ticketNumber" placeholder="TK...">
                        @error('ticketNumber') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Note (optional)</label>
                        <textarea rows="3" class="form-control @error('note') is-invalid @enderror" wire:model="note"></textarea>
                        @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="jara-modal-footer">
                    <button class="btn-secondary" wire:click="closeCreate">Cancel</button>
                    <button class="btn-primary" wire:click="createRefund" wire:loading.attr="disabled">
                        <span wire:loading wire:target="createRefund" class="spinner-sm"></span>
                        Create refund
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══ Mark paid / cancel modal ══ --}}
    @if($refundId && $this->selectedRefund)
        @php $r = $this->selectedRefund; @endphp

        <div class="jara-modal-backdrop" wire:click.self="closeAction">
            <div class="jara-modal">
                <div class="jara-modal-drag"></div>
                <div class="jara-modal-header">
                    <div class="jara-modal-title">{{ $actionType === 'pay' ? 'Mark refund as paid' : 'Cancel refund' }}</div>
                    <button class="jara-modal-close" wire:click="closeAction">✕</button>
                </div>

                <div class="jara-modal-body">
                    <p style="margin:0 0 12px;">
                        <strong>৳{{ number_format((float) $r->amount, 2) }}</strong> to
                        {{ $r->customer?->name }} @if($r->customer?->phone) ({{ $r->customer->phone }}) @endif
                        · Order #{{ $r->order?->order_number }}
                    </p>

                    @if($actionType === 'pay')
                        <div class="form-group">
                            <label class="form-label">How did you send it? <span class="req">*</span></label>
                            <select class="form-control @error('method') is-invalid @enderror" wire:model="method">
                                @foreach($methodLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Transaction reference (optional)</label>
                            <input type="text" class="form-control @error('reference') is-invalid @enderror" wire:model="reference">
                            @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <small style="opacity:.7;">Confirm only after the money has really been sent. The customer will be notified.</small>
                    @else
                        <p style="margin:0;">This refund will not be sent. This cannot be undone.</p>
                    @endif
                </div>

                <div class="jara-modal-footer">
                    <button class="btn-secondary" wire:click="closeAction">Back</button>
                    <button class="btn-primary" wire:click="confirmAction" wire:loading.attr="disabled">
                        <span wire:loading wire:target="confirmAction" class="spinner-sm"></span>
                        {{ $actionType === 'pay' ? 'Confirm paid' : 'Cancel refund' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
