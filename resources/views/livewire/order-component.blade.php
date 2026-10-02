{{-- resources/views/livewire/admin/order-component.blade.php --}}
{{-- Styles: resources/css/blade.css (shared classes, Bootstrap 5 required) --}}
@php
    $statusMap = [
        'pending'    => ['label' => 'Pending',    'class' => 'order-status-new'],
        'confirmed'  => ['label' => 'Confirmed',  'class' => 'order-status-new'],
        'preparing'  => ['label' => 'Preparing',  'class' => 'order-status-preparing'],
        'ready'      => ['label' => 'Ready',      'class' => 'order-status-preparing'],
        'picked_up'  => ['label' => 'On the Way', 'class' => 'order-status-delivering'],
        'on_the_way' => ['label' => 'On the Way', 'class' => 'order-status-delivering'],
        'delivered'  => ['label' => 'Delivered',  'class' => 'order-status-completed'],
        'cancelled'  => ['label' => 'Cancelled',  'class' => 'order-status-default'],
        'rejected'   => ['label' => 'Rejected',   'class' => 'order-status-default'],
    ];
    $statusOf = fn (string $s) => $statusMap[$s] ?? ['label' => ucwords(str_replace('_', ' ', $s)), 'class' => 'order-status-default'];
@endphp
<div>

    {{-- ── Top Bar ── --}}
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">All Orders</div>
        </div>
        <div class="topbar-right">
            <select class="select" wire:model.live="typeFilter">
                <option value="">All Types</option>
                <option value="vendor">Vendor (Food)</option>
                <option value="admin">Admin (Product)</option>
            </select>
            <select class="select" wire:model.live="statusFilter">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="preparing">Preparing</option>
                <option value="ready">Ready</option>
                <option value="picked_up">On the Way</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
                <option value="rejected">Rejected</option>
            </select>
            <button class="export-btn" wire:click="export">
                <span class="material-icons-round">download</span>
                Export
            </button>
        </div>
    </div>

    {{-- ── Table Card ── --}}
    <div class="table-card">

        <div class="toolbar">
            <div class="search-wrap">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                       class="search-input"
                       placeholder="Search by order number, customer or restaurant…"
                       wire:model.live.debounce.350ms="search">
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Seller</th>
                        <th>Rider</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    @php $statusInfo = $statusOf($order->status); @endphp
                    <tr wire:key="order-{{ $order->id }}">
                        <td class="order-id">#{{ $order->order_number }}</td>
                        <td>{{ $order->isAdminOrder() ? 'Product' : 'Food' }}</td>
                        <td class="order-customer">{{ $order->customer->name ?? '—' }}</td>
                        <td class="order-restaurant">
                            {{ $order->isAdminOrder() ? 'KhaiKhai Store' : ($order->restaurant->name ?? '—') }}
                        </td>
                        <td class="order-rider">{{ $order->rider->name ?? '—' }}</td>
                        <td class="text-right order-total">৳{{ number_format((float) $order->total_amount) }}</td>
                        <td class="text-center">
                            <span class="order-status-badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                        </td>
                        <td class="text-center">
                            <button class="icon-btn" wire:click="viewOrder({{ $order->id }})">
                                <span class="material-icons-round">visibility</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="empty">
                            <span class="material-icons-round">receipt_long</span>
                            <p>No orders found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="pagination">
                <small>{{ $orders->firstItem() }}–{{ $orders->lastItem() }} / Total {{ number_format($orders->total()) }}</small>
                {{ $orders->links() }}
            </div>
        @endif

    </div>

    {{-- ── View Order Modal ── --}}
    @if($selectedOrder)
    @php
        $statusInfo = $statusOf($selectedOrder->status);
        $snapshot   = is_array($selectedOrder->delivery_address_snapshot) ? $selectedOrder->delivery_address_snapshot : [];
        $address    = trim(($snapshot['full_address'] ?? '') . ', ' . ($snapshot['city'] ?? ''), ', ');
        $canAdvance = $selectedOrder->isAdminOrder()
                      && isset($nextStatus[$selectedOrder->status])
                      && $selectedOrder->rider_id === null;
        $canUnassign = $selectedOrder->status === 'ready' && $selectedOrder->rider_id !== null;
        // Cancel works for vendor AND product orders; a rider holding it must be unassigned first.
        $canCancel  = in_array($selectedOrder->status, $cancellable, true)
                      && $selectedOrder->rider_id === null;
    @endphp
    <div class="dialog-backdrop" wire:click="closeModal">
        <div class="dialog" wire:click.stop>

            <div class="dialog-header">
                <div class="dialog-title">Order #{{ $selectedOrder->order_number }}</div>
                <button class="dialog-close" wire:click="closeModal">
                    <span class="material-icons-round">close</span>
                </button>
            </div>

            <div class="dialog-body">
                <div class="dialog-status-row">
                    <span class="order-status-badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                    <span class="dialog-date">{{ $selectedOrder->created_at->format('j M Y, g:i A') }}</span>
                </div>

                <div class="dialog-grid">
                    <div class="dialog-field">
                        <div class="dialog-label">Customer</div>
                        <div class="dialog-value">{{ $selectedOrder->customer->name ?? '—' }}</div>
                        @if($selectedOrder->customer?->phone)
                            <div class="dialog-sub">{{ $selectedOrder->customer->phone }}</div>
                        @endif
                    </div>

                    <div class="dialog-field">
                        <div class="dialog-label">Seller</div>
                        <div class="dialog-value">
                            {{ $selectedOrder->isAdminOrder() ? 'KhaiKhai Store (Admin)' : ($selectedOrder->restaurant->name ?? '—') }}
                        </div>
                    </div>

                    <div class="dialog-field">
                        <div class="dialog-label">Rider</div>
                        <div class="dialog-value">{{ $selectedOrder->rider->name ?? '—' }}</div>
                        @if($selectedOrder->rider?->phone)
                            <div class="dialog-sub">{{ $selectedOrder->rider->phone }}</div>
                        @endif
                    </div>

                    <div class="dialog-field">
                        <div class="dialog-label">Payment Method</div>
                        <div class="dialog-value">{{ \Illuminate\Support\Str::headline($selectedOrder->payment_method ?? '') ?: '—' }}</div>
                    </div>
                </div>

                @if($address !== '')
                <div class="dialog-field dialog-field-full">
                    <div class="dialog-label">Delivery Address</div>
                    <div class="dialog-value">{{ $address }}</div>
                </div>
                @endif

                @if($selectedOrder->status === 'cancelled' && $selectedOrder->cancel_reason)
                <div class="dialog-field dialog-field-full">
                    <div class="dialog-label">Cancel Reason</div>
                    <div class="dialog-value">{{ $selectedOrder->cancel_reason }}</div>
                </div>
                @endif

                @if($selectedOrder->items->isNotEmpty())
                <div class="dialog-items">
                    <div class="dialog-label">Items</div>
                    <div class="dialog-items-list">
                        @foreach($selectedOrder->items as $item)
                        <div class="dialog-item-row">
                            <span>{{ $item->quantity }}x {{ $item->item_name }}</span>
                            <span>৳{{ number_format((float) $item->line_total) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="dialog-summary">
                    <div class="dialog-summary-row">
                        <span>Subtotal</span>
                        <span class="dialog-summary-value">৳{{ number_format((float) $selectedOrder->subtotal) }}</span>
                    </div>
                    <div class="dialog-summary-row">
                        <span>Delivery Fee</span>
                        <span class="dialog-summary-value">৳{{ number_format((float) $selectedOrder->delivery_fee) }}</span>
                    </div>
                    <div class="dialog-summary-row">
                        <span>Total</span>
                        <span class="dialog-summary-value dialog-summary-pink">৳{{ number_format((float) $selectedOrder->total_amount) }}</span>
                    </div>
                </div>

                @if($showCancelForm && $canCancel)
                <div class="dialog-field dialog-field-full" style="margin-top:12px;">
                    <div class="dialog-label">Cancel Reason</div>
                    <input type="text" class="form-control" maxlength="255"
                           placeholder="কেন বাতিল করছেন?"
                           wire:model="cancelReason">
                    @error('cancelReason')
                        <div class="text-danger" style="font-size:12px;">{{ $message }}</div>
                    @enderror
                </div>
                @endif
            </div>

            <div class="dialog-footer" style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
                @if($showCancelForm && $canCancel)
                    <button class="btn btn-danger btn-sm" wire:click="cancelSelectedOrder" wire:loading.attr="disabled">
                        Confirm Cancel
                    </button>
                @else
                    @if($canCancel)
                        <button class="btn btn-outline-danger btn-sm" wire:click="openCancelForm">Cancel Order</button>
                    @endif
                    @if($canUnassign)
                        <button class="btn btn-outline-warning btn-sm"
                                wire:click="unassignRider({{ $selectedOrder->id }})"
                                wire:confirm="Remove this rider? The order will go back to other riders."
                                wire:loading.attr="disabled">
                            Unassign Rider
                        </button>
                    @endif
                    @if($canAdvance)
                        <button class="btn btn-success btn-sm"
                                wire:click="advanceStatus({{ $selectedOrder->id }})"
                                wire:loading.attr="disabled">
                            {{ match ($selectedOrder->status) { 'pending' => 'Confirm Order', 'confirmed' => 'Start Preparing', default => 'Mark Ready · Send to Riders' } }}
                        </button>
                    @endif
                @endif
                <button class="dialog-btn-close" wire:click="closeModal">Close</button>
            </div>
        </div>
    </div>
    @endif

</div>