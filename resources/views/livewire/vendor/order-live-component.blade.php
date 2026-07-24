<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="order-alert order-alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="order-alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="order-alert order-alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="order-alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="order-topbar">
            <div class="order-topbar-title">
                <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span>
                <span class="title-emoji">🔴</span>
                Live Orders
                <span class="live-count-badge">{{ $liveOrders->count() }}</span>
            </div>

            <button type="button" wire:click="toggleOnline" class="online-toggle-btn">
                <span class="online-dot" style="background:{{ $isOnline ? '#16a34a' : '#9ca3af' }};"></span>
                <span>{{ $isOnline ? 'Online' : 'Offline' }}</span>
            </button>
        </div>

        {{-- ── Order Cards (OrderListComponent-এর সাথে হুবহু একই স্ট্রাকচার) ── --}}
        <div>
        @forelse ($liveOrders as $order)

            @php
                $statusMeta  = $this->statusMeta($order->status);
                $paymentMeta = $this->paymentMeta($order->payment_status);
                $actionLabel = $this->nextActionLabel($order->status);
                $itemCount   = $order->items->count();
            @endphp

            <div class="order-card" wire:key="live-order-{{ $order->id }}">

                {{-- Top Row --}}
                <div class="order-card-top">
                    <div class="order-card-id">
                        <span class="order-number">#{{ $order->order_number }}</span>
                        <span class="order-time">{{ $order->created_at->diffForHumans() }}</span>
                    </div>
                    <span class="order-status-badge {{ $statusMeta['class'] }}">
                        {{ $statusMeta['emoji'] }} {{ $statusMeta['label'] }}
                    </span>
                </div>

                {{-- Customer + items --}}
                <div class="order-card-meta">
                    <span class="order-meta-item">
                        <span class="material-icons-round">person</span>
                        {{ $order->customer->name ?? 'Customer' }}
                    </span>
                    <span class="order-meta-item">
                        <span class="material-icons-round">shopping_bag</span>
                        {{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }}
                    </span>
                </div>

                {{-- Payment row --}}
                <div class="order-card-meta">
                    <span class="order-pay-pill">{{ $this->paymentMethodLabel($order->payment_method) }}</span>
                    <span class="order-payment-badge {{ $paymentMeta['class'] }}">{{ $paymentMeta['label'] }}</span>
                </div>

                {{-- Bottom Row --}}
                <div class="order-card-bottom">
                    <span class="order-price-badge">৳{{ number_format($order->total_amount, 0) }}</span>

                    <div class="order-card-actions">
                        <button class="order-btn-details" wire:click="openDetails({{ $order->id }})">
                            <span class="material-icons-round">visibility</span>
                            Details
                        </button>

                        @if($order->status === 'pending')
                            <button class="order-btn-advance"
                                wire:click="advanceStatus({{ $order->id }})"
                                wire:loading.attr="disabled"
                                wire:target="advanceStatus({{ $order->id }}), confirmRejectRecord({{ $order->id }})">
                                Confirm Order
                            </button>
                            <button class="order-btn-cancel"
                                wire:click="confirmRejectRecord({{ $order->id }})"
                                wire:loading.attr="disabled"
                                wire:target="advanceStatus({{ $order->id }}), confirmRejectRecord({{ $order->id }})">
                                <span class="material-icons-round">close</span>
                            </button>
                        @elseif($actionLabel)
                            <button class="order-btn-advance"
                                wire:click="advanceStatus({{ $order->id }})"
                                wire:loading.attr="disabled"
                                wire:target="advanceStatus({{ $order->id }})">
                                {{ $actionLabel }}
                            </button>
                        @endif
                    </div>
                </div>

            </div>

        @empty
            <div class="order-empty">
                <i class="bi bi-receipt order-empty-icon"></i>
                <p>No live orders right now.</p>
            </div>
        @endforelse
        </div>
    </div>

    {{-- ══════════════════════════════════════
        Order Details Modal (OrderListComponent-এর সাথে হুবহু একই)
        ══════════════════════════════════════ --}}
    @if($showDetailsModal)
        <div class="order-modal-backdrop"
             x-data
             @click.self="$wire.closeDetails()">
            <div class="order-modal">

                <div class="order-modal-drag"></div>

                <div class="order-modal-header">
                    <div class="order-modal-title">
                        🧾 Order #{{ $detailsOrder->order_number ?? '' }}
                    </div>
                    <button class="order-modal-close" wire:click="closeDetails">✕</button>
                </div>

                <div class="order-modal-body">
                    @if($detailsOrder)
                        @php
                            $dMeta = $this->statusMeta($detailsOrder->status);
                            $pMeta = $this->paymentMeta($detailsOrder->payment_status);
                            $addr  = is_array($detailsOrder->delivery_address_snapshot)
                                        ? $detailsOrder->delivery_address_snapshot
                                        : [];
                        @endphp

                        {{-- Status + Customer --}}
                        <div class="order-detail-section">
                            <span class="order-status-badge {{ $dMeta['class'] }}">{{ $dMeta['emoji'] }} {{ $dMeta['label'] }}</span>
                            <div class="order-detail-row">
                                <span class="material-icons-round">person</span>
                                <strong>{{ $detailsOrder->customer->name ?? 'Customer' }}</strong>
                                @if(!empty($detailsOrder->customer->phone))
                                    &nbsp;·&nbsp;{{ $detailsOrder->customer->phone }}
                                @endif
                            </div>
                            @if($detailsOrder->rider)
                                <div class="order-detail-row">
                                    <span class="material-icons-round">moped</span>
                                    Rider: {{ $detailsOrder->rider->name }}
                                </div>
                            @endif
                        </div>

                        {{-- Delivery address --}}
                        <div class="order-detail-section">
                            <div class="order-detail-label">Delivery Address</div>
                            <div class="order-detail-row">
                                <span class="material-icons-round">location_on</span>
                                <span>
                                    {{ $addr['address_line'] ?? $addr['address'] ?? '—' }}
                                    @if(!empty($addr['area'])), {{ $addr['area'] }}@endif
                                    @if(!empty($addr['city'])), {{ $addr['city'] }}@endif
                                </span>
                            </div>
                            @if(!empty($addr['landmark']))
                                <div class="order-detail-row order-detail-sub">Landmark: {{ $addr['landmark'] }}</div>
                            @endif
                        </div>

                        {{-- Items --}}
                        <div class="order-detail-section">
                            <div class="order-detail-label">Items</div>
                            @foreach($detailsOrder->items as $line)
                                <div class="order-line-item">
                                    <span class="order-line-emoji">{{ $line->emoji ?? '🍽️' }}</span>
                                    <span class="order-line-name">{{ $line->item_name }} × {{ $line->quantity }}</span>
                                    <span class="order-line-total">৳{{ number_format($line->line_total, 0) }}</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Totals --}}
                        <div class="order-detail-section order-totals">
                            <div class="order-total-row">
                                <span>Subtotal</span>
                                <span>৳{{ number_format($detailsOrder->subtotal, 0) }}</span>
                            </div>
                            <div class="order-total-row">
                                <span>Delivery Fee</span>
                                <span>৳{{ number_format($detailsOrder->delivery_fee, 0) }}</span>
                            </div>
                            @if($detailsOrder->discount_amount > 0)
                                <div class="order-total-row order-total-discount">
                                    <span>Discount</span>
                                    <span>−৳{{ number_format($detailsOrder->discount_amount, 0) }}</span>
                                </div>
                            @endif
                            <div class="order-total-row order-total-grand">
                                <span>Total</span>
                                <span>৳{{ number_format($detailsOrder->total_amount, 0) }}</span>
                            </div>
                        </div>

                        {{-- Payment --}}
                        <div class="order-detail-section">
                            <div class="order-detail-row">
                                <span class="material-icons-round">payments</span>
                                {{ $this->paymentMethodLabel($detailsOrder->payment_method) }}
                                <span class="order-payment-badge {{ $pMeta['class'] }}" style="margin-left:8px;">{{ $pMeta['label'] }}</span>
                            </div>
                        </div>

                        {{-- Special instructions --}}
                        @if($detailsOrder->special_instructions)
                            <div class="order-detail-section">
                                <div class="order-detail-label">Special Instructions</div>
                                <div class="order-detail-note">{{ $detailsOrder->special_instructions }}</div>
                            </div>
                        @endif

                        {{-- Cancel reason --}}
                        @if($detailsOrder->status === 'cancelled' && $detailsOrder->cancel_reason)
                            <div class="order-detail-section">
                                <div class="order-detail-label">Cancellation Reason</div>
                                <div class="order-detail-note order-detail-note-danger">{{ $detailsOrder->cancel_reason }}</div>
                            </div>
                        @endif

                        {{-- Status timeline --}}
                        @if($detailsOrder->statusLogs && $detailsOrder->statusLogs->count())
                            <div class="order-detail-section">
                                <div class="order-detail-label">Status Timeline</div>
                                @foreach($detailsOrder->statusLogs as $log)
                                    <div class="order-timeline-row">
                                        <span class="order-timeline-dot"></span>
                                        <div>
                                            <div class="order-timeline-text">
                                                {{ $log->from_status ? $this->statusMeta($log->from_status)['label'] . ' → ' : '' }}{{ $this->statusMeta($log->to_status)['label'] }}
                                            </div>
                                            <div class="order-timeline-sub">
                                                {{ $log->created_at->format('d M, h:i A') }}
                                                @if($log->changedBy) · {{ $log->changedBy->name }} @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>

                <div class="order-modal-footer">
                    <button class="btn-order-secondary" wire:click="closeDetails">Close</button>
                </div>

            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════
         Reject Confirmation Modal
         ══════════════════════════════════════ --}}
    @if($confirmReject)
        <div class="order-modal-backdrop">
            <div class="order-cancel-modal">
                <div class="order-cancel-icon">⚠️</div>
                <h6>Cancel This Order?</h6>
                <p>Please tell the customer why. This action cannot be undone.</p>

                <textarea class="order-form-control @error('reject_reason') is-invalid @enderror"
                    wire:model="reject_reason"
                    rows="3"
                    placeholder="e.g. Item out of stock, restaurant closing early..."></textarea>
                @error('reject_reason') <div class="order-invalid-feedback">{{ $message }}</div> @enderror

                <div class="order-cancel-actions">
                    <button class="btn-cancel" wire:click="$set('confirmReject', false)">Back</button>
                    <button class="btn-confirm-cancel" wire:click="rejectOrder" wire:loading.attr="disabled" wire:target="rejectOrder">
                        <span wire:loading wire:target="rejectOrder" class="spinner-sm"></span>
                        Cancel Order
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>

@push('styles')
    <style>

        .main-content { background: var(--bg); min-height: 100vh; padding: 0 0 80px; font-family: var(--font); }

        .order-topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 16px 12px; background: var(--bg); position: sticky; top: 0; z-index: 50;
        }
        .order-topbar-title { display: flex; align-items: center; gap: 8px; font-size: 1.18rem; font-weight: 700; color: var(--dark); }
        .order-topbar-title .title-emoji { font-size: 1.2rem; }
        .live-count-badge { background: #fce7f3; color: var(--pink); font-weight: 800; font-size: .74rem; padding: 3px 10px; border-radius: 50px; }

        .online-toggle-btn { display: flex; align-items: center; gap: 8px; background: none; border: none; cursor: pointer; padding: 0; font-size: .8rem; color: var(--soft-dark); font-weight: 600; }
        .online-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }

        .order-card {
            margin: 0 16px 12px; background: var(--card-bg); border-radius: var(--radius-lg);
            padding: 16px; box-shadow: var(--shadow-card); border: 1.5px solid var(--border);
            transition: var(--transition); position: relative; overflow: hidden;
        }
        .order-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--pink); border-radius: 4px 0 0 4px; opacity: 0; transition: var(--transition); }
        .order-card:hover { box-shadow: var(--shadow-hover); border-color: rgba(255,61,139,.2); transform: translateY(-2px); }
        .order-card:hover::before { opacity: 1; }

        .order-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
        .order-card-id { display: flex; flex-direction: column; gap: 2px; }
        .order-number { font-size: .98rem; font-weight: 700; color: var(--dark); }
        .order-time { font-size: .74rem; color: var(--muted); }

        .order-status-badge { flex-shrink: 0; padding: 3px 10px; border-radius: 50px; font-size: .68rem; font-weight: 600; font-family: var(--font); white-space: nowrap; }
        .order-status-badge.pending    { background: #FFF4E0; color: #C9820A; }
        .order-status-badge.confirmed  { background: #E7F0FF; color: #2563EB; }
        .order-status-badge.preparing  { background: #F3E8FF; color: #7C3AED; }
        .order-status-badge.picked-up  { background: #E0F7FA; color: #0097A7; }
        .order-status-badge.delivered  { background: #E8FAF0; color: #1A9453; }
        .order-status-badge.cancelled  { background: #FFF0F0; color: #E53935; }

        .order-card-meta { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 6px; }
        .order-meta-item { display: inline-flex; align-items: center; gap: 4px; font-size: .78rem; color: var(--soft-dark); }
        .order-meta-item .material-icons-round { font-size: .9rem; color: var(--muted); }

        .order-pay-pill { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 50px; font-size: .7rem; font-weight: 600; background: var(--pink-light); color: var(--pink); }
        .order-payment-badge { padding: 3px 10px; border-radius: 50px; font-size: .68rem; font-weight: 600; }
        .order-payment-badge.pending  { background: #FFF4E0; color: #C9820A; }
        .order-payment-badge.paid     { background: #E8FAF0; color: #1A9453; }
        .order-payment-badge.refunded { background: #E7F0FF; color: #2563EB; }
        .order-payment-badge.failed   { background: #FFF0F0; color: #E53935; }

        .order-card-bottom { display: flex; align-items: center; justify-content: space-between; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border); gap: 8px; flex-wrap: wrap; }
        .order-price-badge { font-size: .9rem; font-weight: 700; color: var(--pink); }

        .order-card-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

        .order-btn-details {
            display: inline-flex; align-items: center; gap: 5px; background: #F5F5FB; border: none;
            border-radius: var(--radius-sm); padding: 7px 14px; font-size: .78rem; font-family: var(--font);
            color: var(--soft-dark); cursor: pointer; transition: var(--transition); font-weight: 600;
        }
        .order-btn-details:hover { background: var(--pink-light); color: var(--pink); }
        .order-btn-details .material-icons-round { font-size: .95rem; }

        .order-btn-advance {
            background: var(--pink); color: #fff; border: none; border-radius: var(--radius-sm);
            padding: 7px 14px; font-size: .78rem; font-family: var(--font); font-weight: 600;
            cursor: pointer; transition: var(--transition);
        }
        .order-btn-advance:hover { background: #e02d7a; }
        .order-btn-advance:disabled { opacity: .6; cursor: not-allowed; }

        .order-btn-cancel {
            display: inline-flex; align-items: center; justify-content: center; background: #FFF0F0;
            border: none; border-radius: var(--radius-sm); padding: 7px 9px; color: #E53935;
            cursor: pointer; transition: var(--transition);
        }
        .order-btn-cancel:hover { background: #FFD6D6; }
        .order-btn-cancel:disabled { opacity: .6; cursor: not-allowed; }
        .order-btn-cancel .material-icons-round { font-size: .95rem; }

        .order-empty { text-align: center; padding: 60px 20px; }
        .order-empty-icon { font-size: 3rem; opacity: .25; display: block; margin-bottom: 12px; }
        .order-empty p { color: var(--muted); font-size: .88rem; margin: 0; }

        .order-alert { display: flex; align-items: center; gap: 8px; margin: 12px 16px; padding: 12px 14px; border-radius: var(--radius-md); font-size: .82rem; }
        .order-alert span { flex: 1; }
        .order-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
        .order-alert-error   { background: #FFF0F0; color: #E53935; border: 1px solid #FFBCBC; }
        .order-alert-close { background: none; border: none; cursor: pointer; font-size: 1.1rem; color: inherit; padding: 0; line-height: 1; }

        .order-modal-backdrop {
            position: fixed; inset: 0; background: rgba(10,10,30,.55); z-index: 1000;
            display: flex; align-items: flex-end; justify-content: center; animation: fadeIn .18s ease;
        }
        @keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }

        .order-modal {
            background: var(--card-bg); border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            width: 100%; max-width: 600px; max-height: 92vh; display: flex; flex-direction: column;
            animation: slideUp .22s cubic-bezier(.4,0,.2,1);
        }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @media (min-width: 640px) {
            .order-modal-backdrop { align-items: center; padding: 20px; }
            .order-modal { border-radius: var(--radius-lg); max-height: 88vh; }
        }

        .order-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 18px 0; flex-shrink: 0; }
        .order-modal-title { font-size: 1rem; font-weight: 700; color: var(--dark); }
        .order-modal-close {
            width: 32px; height: 32px; border-radius: 50%; background: var(--bg); border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center; color: var(--soft-dark); font-size: 1rem; transition: var(--transition);
        }
        .order-modal-close:hover { background: var(--pink-light); color: var(--pink); }

        .order-modal-drag { width: 40px; height: 4px; background: var(--border); border-radius: 4px; margin: 10px auto 0; flex-shrink: 0; }

        .order-modal-body { overflow-y: auto; padding: 18px; flex: 1; }
        .order-modal-body::-webkit-scrollbar { width: 4px; }
        .order-modal-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        .order-modal-footer { display: flex; gap: 10px; padding: 14px 18px; border-top: 1px solid var(--border); flex-shrink: 0; }

        .order-detail-section { padding: 12px 0; border-bottom: 1px solid var(--border); }
        .order-detail-section:last-child { border-bottom: none; }
        .order-detail-label { font-size: .76rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; margin-bottom: 8px; }
        .order-detail-row { display: flex; align-items: center; gap: 8px; font-size: .85rem; color: var(--dark); margin-top: 6px; }
        .order-detail-row .material-icons-round { font-size: 1rem; color: var(--muted); }
        .order-detail-sub { font-size: .76rem; color: var(--muted); margin-top: 2px; }
        .order-detail-note { font-size: .82rem; color: var(--soft-dark); background: var(--bg); border-radius: var(--radius-sm); padding: 10px 12px; line-height: 1.5; }
        .order-detail-note-danger { background: #FFF0F0; color: #C62828; }

        .order-line-item { display: flex; align-items: center; gap: 10px; padding: 6px 0; font-size: .84rem; }
        .order-line-emoji { font-size: 1.1rem; flex-shrink: 0; }
        .order-line-name { flex: 1; color: var(--dark); }
        .order-line-total { font-weight: 600; color: var(--soft-dark); }

        .order-totals { background: var(--bg); border-radius: var(--radius-sm); padding: 12px 14px; border-bottom: none !important; }
        .order-total-row { display: flex; justify-content: space-between; font-size: .82rem; color: var(--soft-dark); padding: 4px 0; }
        .order-total-discount { color: #1A9453; }
        .order-total-grand { font-size: .95rem; font-weight: 700; color: var(--dark); border-top: 1px dashed var(--border); margin-top: 6px; padding-top: 8px; }

        .order-timeline-row { display: flex; align-items: flex-start; gap: 10px; padding: 6px 0; }
        .order-timeline-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--pink); margin-top: 6px; flex-shrink: 0; }
        .order-timeline-text { font-size: .82rem; font-weight: 600; color: var(--dark); }
        .order-timeline-sub { font-size: .72rem; color: var(--muted); margin-top: 1px; }

        .btn-order-secondary {
            flex: 1; padding: 11px 20px; background: var(--bg); color: var(--soft-dark); border: 1.5px solid var(--border);
            border-radius: var(--radius-md); font-family: var(--font); font-size: .88rem; font-weight: 600; cursor: pointer; transition: var(--transition);
        }
        .btn-order-secondary:hover { background: var(--border); }

        .order-cancel-modal {
            background: var(--card-bg); border-radius: var(--radius-lg); max-width: 360px; width: calc(100% - 32px);
            padding: 28px 20px 20px; text-align: center; animation: scaleIn .18s cubic-bezier(.4,0,.2,1);
        }
        @keyframes scaleIn { from { transform: scale(.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .order-cancel-icon { width: 56px; height: 56px; border-radius: 50%; background: #FFF0F0; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; font-size: 1.6rem; }
        .order-cancel-modal h6 { font-size: .98rem; font-weight: 700; color: var(--dark); margin: 0 0 6px; }
        .order-cancel-modal p { font-size: .8rem; color: var(--muted); margin: 0 0 14px; line-height: 1.5; }

        .order-form-control {
            width: 100%; padding: 10px 12px; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-family: var(--font); font-size: .84rem; color: var(--dark); background: var(--bg); outline: none;
            transition: var(--transition); box-sizing: border-box; text-align: left; resize: vertical;
        }
        .order-form-control:focus { border-color: var(--pink); box-shadow: 0 0 0 3px rgba(255,61,139,.1); background: #fff; }
        .order-form-control.is-invalid { border-color: #E53935; }
        .order-invalid-feedback { color: #E53935; font-size: .74rem; margin-top: 4px; text-align: left; }

        .order-cancel-actions { display: flex; gap: 10px; justify-content: center; margin-top: 16px; }

        .btn-cancel { padding: 9px 20px; background: var(--bg); border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: var(--font); font-size: .82rem; font-weight: 600; color: var(--soft-dark); cursor: pointer; transition: var(--transition); }
        .btn-cancel:hover { background: var(--border); }

        .btn-confirm-cancel {
            padding: 9px 20px; background: #E53935; border: none; border-radius: var(--radius-sm); font-family: var(--font);
            font-size: .82rem; font-weight: 600; color: #fff; cursor: pointer; transition: var(--transition); display: flex; align-items: center; gap: 5px;
        }
        .btn-confirm-cancel:hover { background: #c62828; }
        .btn-confirm-cancel:disabled { opacity: .6; cursor: not-allowed; }

        .spinner-sm { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 400px) {
            .order-topbar-title { font-size: 1rem; }
        }

    </style>
@endpush