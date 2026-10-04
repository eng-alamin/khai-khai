{{-- resources/views/livewire/admin/support-inbox-component.blade.php --}}
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
        $statusLabels = [
            'open'        => 'Open',
            'in_progress' => 'In progress',
            'resolved'    => 'Resolved',
            'closed'      => 'Closed',
        ];
        $categoryLabels = [
            'order'        => 'Order problem',
            'payment'      => 'Payment problem',
            'delivery'     => 'Delivery problem',
            'food_quality' => 'Food quality',
            'account'      => 'Account problem',
            'refund'       => 'Refund request',
            'other'        => 'Other',
        ];
        $badgeOf = fn ($s) => match ($s) {
            'resolved'   => 'available',
            'closed'     => 'unavailable',
            default      => 'pending',
        };
    @endphp

    <div class="main-content">

        <div class="topbar">
            <div class="topbar-title">
                <span class="title-emoji">🎧</span>
                Support Inbox
            </div>
        </div>

        {{-- ── Stats ── --}}
        <div class="mini-stats">
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(239,68,68,.12);color:#dc2626">
                    <span class="material-icons-round">mark_email_unread</span>
                </div>
                <div>
                    <div class="mini-stat-value">{{ (int) ($counts['open'] ?? 0) }}</div>
                    <div class="mini-stat-label">Open</div>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(59,130,246,.12);color:#2563eb">
                    <span class="material-icons-round">pending_actions</span>
                </div>
                <div>
                    <div class="mini-stat-value">{{ (int) ($counts['in_progress'] ?? 0) }}</div>
                    <div class="mini-stat-label">In progress</div>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(34,197,94,.12);color:#16a34a">
                    <span class="material-icons-round">task_alt</span>
                </div>
                <div>
                    <div class="mini-stat-value">{{ (int) ($counts['resolved'] ?? 0) }}</div>
                    <div class="mini-stat-label">Resolved</div>
                </div>
            </div>
        </div>

        {{-- ── Search + filter ── --}}
        <div class="filterbar">
            <div class="search-inner filterbar-search">
                <span class="material-icons-round search-icon">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search ticket no., subject, customer name or phone...">
            </div>
            <div class="filterbar-selects">
                <select class="select" wire:model.live="statusFilter">
                    <option value="">All tickets</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ── Ticket cards ── --}}
        @forelse($tickets as $ticket)
            <div class="card" wire:key="ticket-{{ $ticket->id }}">
                <div class="card-top">
                    <div class="card-thumb">{{ strtoupper(substr($ticket->customer?->name ?? '?', 0, 1)) }}</div>
                    <div class="card-info">
                        <div class="card-title">{{ $ticket->subject }}</div>
                        <div class="card-desc">
                            #{{ $ticket->ticket_number }} · {{ $ticket->customer?->name ?? 'Deleted customer' }}
                            @if($ticket->customer?->phone) · {{ $ticket->customer->phone }} @endif
                            · {{ $ticket->created_at->format('d M Y, h:i A') }}
                        </div>
                    </div>
                    <div class="badges-col">
                        <span class="status-badge {{ $badgeOf($ticket->status) }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
                    </div>
                </div>

                <div class="card-meta">
                    <span class="cat-pill">{{ $categoryLabels[$ticket->category] ?? $ticket->category }}</span>
                    <span class="meta-item">{{ \Illuminate\Support\Str::limit($ticket->message, 110) }}</span>
                    @if($ticket->messages_count > 0)
                        <span class="meta-item">💬 {{ $ticket->messages_count }}</span>
                    @endif
                </div>

                <div class="card-bottom">
                    <div></div>
                    <div class="card-actions">
                        <button class="btn-approve" wire:click="openTicket({{ $ticket->id }})">
                            <span class="material-icons-round">{{ $ticket->status === 'closed' ? 'visibility' : 'reply' }}</span>
                            {{ $ticket->status === 'closed' ? 'View' : 'Open / reply' }}
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty">
                <span class="material-icons-round empty-icon">inbox</span>
                <p>No tickets found.</p>
            </div>
        @endforelse

        @if($tickets->hasPages())
            <div class="pagination">
                <small>Showing {{ $tickets->firstItem() ?? 0 }}–{{ $tickets->lastItem() ?? 0 }} of {{ $tickets->total() }} total</small>
                {{ $tickets->links() }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══ Ticket modal ══ --}}
    @if($ticketId && $this->selectedTicket)
        @php
            $t      = $this->selectedTicket;
            $locked = $t->status === 'closed';
        @endphp

        <div class="jara-modal-backdrop" wire:click.self="closeTicket">
            <div class="jara-modal">
                <div class="jara-modal-drag"></div>

                <div class="jara-modal-header">
                    <div class="jara-modal-title">🎧 #{{ $t->ticket_number }}</div>
                    <button class="jara-modal-close" wire:click="closeTicket">✕</button>
                </div>

                <div class="jara-modal-body">
                    <p style="margin:0 0 4px;">
                        <strong>{{ $t->customer?->name ?? 'Deleted customer' }}</strong>
                        @if($t->customer?->phone) ({{ $t->customer->phone }}) @endif
                    </p>
                    <p style="margin:0 0 12px;opacity:.75;font-size:13px;">
                        {{ $categoryLabels[$t->category] ?? $t->category }}
                        · {{ $t->created_at->format('d M Y, h:i A') }}
                        @if($t->order) · Order #{{ $t->order->order_number }} @endif
                    </p>

                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <div>{{ $t->subject }}</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Conversation</label>

                        <div style="margin-bottom:8px;padding:10px 12px;border-radius:8px;background:rgba(0,0,0,.04);">
                            <small style="opacity:.7;">{{ $t->customer?->name ?? 'Customer' }} · {{ $t->created_at->format('d M Y, h:i A') }}</small>
                            <div style="white-space:pre-line;">{{ $t->message }}</div>
                        </div>

                        @foreach($t->messages as $m)
                            @php $fromAdmin = $m->sender_role === 'admin'; @endphp
                            <div wire:key="msg-{{ $m->id }}"
                                 style="margin-bottom:8px;padding:10px 12px;border-radius:8px;{{ $fromAdmin ? 'background:rgba(59,130,246,.10);margin-left:24px;' : 'background:rgba(0,0,0,.04);' }}">
                                <small style="opacity:.7;">
                                    {{ $fromAdmin ? ($m->sender?->name ?? 'Support') . ' (support)' : ($t->customer?->name ?? 'Customer') }}
                                    · {{ $m->created_at->format('d M Y, h:i A') }}
                                </small>
                                <div style="white-space:pre-line;">{{ $m->body }}</div>
                            </div>
                        @endforeach
                    </div>

                    @if($t->order)
                        <p style="margin:0 0 12px;">
                            <a href="{{ route('admin.refunds', ['order' => $t->order->order_number, 'ticket' => $t->ticket_number]) }}" style="font-size:13px;">
                                💸 Create a refund for order #{{ $t->order->order_number }}
                            </a>
                        </p>
                    @endif

                    @if($locked)
                        <small style="opacity:.7;">This ticket is closed and cannot be changed.</small>
                    @else
                        <div class="form-group">
                            <label class="form-label">Your reply <span class="req">*</span></label>
                            <textarea rows="5" class="form-control @error('reply') is-invalid @enderror" wire:model="reply" placeholder="Write the answer the customer will see..."></textarea>
                            @error('reply') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Set status <span class="req">*</span></label>
                            <select class="form-control @error('status') is-invalid @enderror" wire:model="status">
                                <option value="in_progress">In progress (still working on it)</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed (final)</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif
                </div>

                <div class="jara-modal-footer">
                    <button class="btn-secondary" wire:click="closeTicket">{{ $locked ? 'Close' : 'Cancel' }}</button>
                    @unless($locked)
                        <button class="btn-secondary" wire:click="changeStatus" wire:loading.attr="disabled">Change status only</button>
                        <button class="btn-primary" wire:click="sendReply" wire:loading.attr="disabled">
                            <span wire:loading wire:target="sendReply" class="spinner-sm"></span>
                            Send reply
                        </button>
                    @endunless
                </div>
            </div>
        </div>
    @endif

</div>
