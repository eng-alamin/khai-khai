<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="topbar">
            <div class="topbar-title">
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

            <div class="card" wire:key="live-order-{{ $order->id }}">

                {{-- Top Row --}}
                <div class="card-top">
                    <div class="card-id">
                        <span class="order-number">#{{ $order->order_number }}</span>
                        <span class="order-time">{{ $order->created_at->diffForHumans() }}</span>
                    </div>
                    <span class="status-badge {{ $statusMeta['class'] }}">
                        {{ $statusMeta['emoji'] }} {{ $statusMeta['label'] }}
                    </span>
                </div>

                {{-- Customer + items --}}
                <div class="card-meta">
                    <span class="meta-item">
                        <span class="material-icons-round">person</span>
                        {{ $order->customer->name ?? 'Customer' }}
                    </span>
                    <span class="meta-item">
                        <span class="material-icons-round">shopping_bag</span>
                        {{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }}
                    </span>
                </div>

                {{-- Payment row --}}
                <div class="card-meta">
                    <span class="pay-pill">{{ $this->paymentMethodLabel($order->payment_method) }}</span>
                    <span class="status-badge {{ $paymentMeta['class'] }}">{{ $paymentMeta['label'] }}</span>
                </div>

                {{-- Bottom Row --}}
                <div class="card-bottom">
                    <span class="price-badge">৳{{ number_format($order->total_amount, 0) }}</span>

                    <div class="card-actions">
                        <button class="btn-details" wire:click="openDetails({{ $order->id }})">
                            <span class="material-icons-round">visibility</span>
                            Details
                        </button>

                        @if($order->status === 'pending')
                            <button class="btn-advance"
                                wire:click="advanceStatus({{ $order->id }})"
                                wire:loading.attr="disabled"
                                wire:target="advanceStatus({{ $order->id }}), confirmRejectRecord({{ $order->id }})">
                                Confirm Order
                            </button>
                            <button class="btn-delete"
                                wire:click="confirmRejectRecord({{ $order->id }})"
                                wire:loading.attr="disabled"
                                wire:target="advanceStatus({{ $order->id }}), confirmRejectRecord({{ $order->id }})">
                                <span class="material-icons-round">close</span>
                            </button>
                        @elseif($actionLabel)
                            <button class="btn-advance"
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
            <div class="empty">
                <i class="bi bi-receipt empty-icon"></i>
                <p>No live orders right now.</p>
            </div>
        @endforelse
        </div>
    </div>

    {{-- ══════════════════════════════════════
        Order Details Modal (OrderListComponent-এর সাথে হুবহু একই)
        ══════════════════════════════════════ --}}
    @if($showDetailsModal)
        <div class="jara-modal-backdrop"
             x-data
             @click.self="$wire.closeDetails()">
            <div class="jara-modal">

                <div class="jara-modal-drag"></div>

                <div class="jara-modal-header">
                    <div class="jara-modal-title">
                        🧾 Order #{{ $detailsOrder->order_number ?? '' }}
                    </div>
                    <button class="jara-modal-close" wire:click="closeDetails">✕</button>
                </div>

                <div class="jara-modal-body">
                    @if($detailsOrder)
                        @php
                            $dMeta = $this->statusMeta($detailsOrder->status);
                            $pMeta = $this->paymentMeta($detailsOrder->payment_status);
                            $addr  = is_array($detailsOrder->delivery_address_snapshot)
                                        ? $detailsOrder->delivery_address_snapshot
                                        : [];
                        @endphp

                        {{-- Status + Customer --}}
                        <div class="detail-section">
                            <span class="status-badge {{ $dMeta['class'] }}">{{ $dMeta['emoji'] }} {{ $dMeta['label'] }}</span>
                            <div class="detail-row">
                                <span class="material-icons-round">person</span>
                                <strong>{{ $detailsOrder->customer->name ?? 'Customer' }}</strong>
                                @if(!empty($detailsOrder->customer->phone))
                                    &nbsp;·&nbsp;{{ $detailsOrder->customer->phone }}
                                @endif
                            </div>
                            @if($detailsOrder->rider)
                                <div class="detail-row">
                                    <span class="material-icons-round">moped</span>
                                    Rider: {{ $detailsOrder->rider->name }}
                                </div>
                            @endif
                        </div>

                        {{-- Delivery address --}}
                        <div class="detail-section">
                            <div class="order-detail-label">Delivery Address</div>
                            <div class="detail-row">
                                <span class="material-icons-round">location_on</span>
                                <span>
                                    {{ $addr['address_line'] ?? $addr['address'] ?? '—' }}
                                    @if(!empty($addr['area'])), {{ $addr['area'] }}@endif
                                    @if(!empty($addr['city'])), {{ $addr['city'] }}@endif
                                </span>
                            </div>
                            @if(!empty($addr['landmark']))
                                <div class="detail-row detail-sub">Landmark: {{ $addr['landmark'] }}</div>
                            @endif
                        </div>

                        {{-- Items --}}
                        <div class="detail-section">
                            <div class="order-detail-label">Items</div>
                            @foreach($detailsOrder->items as $line)
                                <div class="line-item">
                                    <span class="line-emoji">{{ $line->emoji ?? '🍽️' }}</span>
                                    <span class="line-name">{{ $line->item_name }} × {{ $line->quantity }}</span>
                                    <span class="line-total">৳{{ number_format($line->line_total, 0) }}</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Totals --}}
                        <div class="detail-section order-totals">
                            <div class="total-row">
                                <span>Subtotal</span>
                                <span>৳{{ number_format($detailsOrder->subtotal, 0) }}</span>
                            </div>
                            <div class="total-row">
                                <span>Delivery Fee</span>
                                <span>৳{{ number_format($detailsOrder->delivery_fee, 0) }}</span>
                            </div>
                            @if($detailsOrder->discount_amount > 0)
                                <div class="total-row total-discount">
                                    <span>Discount</span>
                                    <span>−৳{{ number_format($detailsOrder->discount_amount, 0) }}</span>
                                </div>
                            @endif
                            <div class="total-row total-grand">
                                <span>Total</span>
                                <span>৳{{ number_format($detailsOrder->total_amount, 0) }}</span>
                            </div>
                        </div>

                        {{-- Payment --}}
                        <div class="detail-section">
                            <div class="detail-row">
                                <span class="material-icons-round">payments</span>
                                {{ $this->paymentMethodLabel($detailsOrder->payment_method) }}
                                <span class="status-badge {{ $pMeta['class'] }}" style="margin-left:8px;">{{ $pMeta['label'] }}</span>
                            </div>
                        </div>

                        {{-- Special instructions --}}
                        @if($detailsOrder->special_instructions)
                            <div class="detail-section">
                                <div class="order-detail-label">Special Instructions</div>
                                <div class="detail-note">{{ $detailsOrder->special_instructions }}</div>
                            </div>
                        @endif

                        {{-- Cancel reason --}}
                        @if($detailsOrder->status === 'cancelled' && $detailsOrder->cancel_reason)
                            <div class="detail-section">
                                <div class="order-detail-label">Cancellation Reason</div>
                                <div class="detail-note detail-note-danger">{{ $detailsOrder->cancel_reason }}</div>
                            </div>
                        @endif

                        {{-- Status timeline --}}
                        @if($detailsOrder->statusLogs && $detailsOrder->statusLogs->count())
                            <div class="detail-section">
                                <div class="order-detail-label">Status Timeline</div>
                                @foreach($detailsOrder->statusLogs as $log)
                                    <div class="timeline-row">
                                        <span class="timeline-dot"></span>
                                        <div>
                                            <div class="timeline-text">
                                                {{ $log->from_status ? $this->statusMeta($log->from_status)['label'] . ' → ' : '' }}{{ $this->statusMeta($log->to_status)['label'] }}
                                            </div>
                                            <div class="timeline-sub">
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

                <div class="jara-modal-footer">
                    <button class="btn-secondary" wire:click="closeDetails">Close</button>
                </div>

            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════
         Reject Confirmation Modal
         ══════════════════════════════════════ --}}
    @if($confirmReject)
        <div class="jara-modal-backdrop">
            <div class="jara-cancel-modal">
                <div class="delete-icon">⚠️</div>
                <h6>Cancel This Order?</h6>
                <p>Please tell the customer why. This action cannot be undone.</p>

                <textarea class="form-control @error('reject_reason') is-invalid @enderror"
                    wire:model="reject_reason"
                    rows="3"
                    placeholder="e.g. Item out of stock, restaurant closing early..."></textarea>
                @error('reject_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror

                <div class="delete-actions">
                    <button class="btn-cancel" wire:click="$set('confirmReject', false)">Back</button>
                    <button class="btn-confirm-delete" wire:click="rejectOrder" wire:loading.attr="disabled" wire:target="rejectOrder">
                        <span wire:loading wire:target="rejectOrder" class="spinner-sm"></span>
                        Cancel Order
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
