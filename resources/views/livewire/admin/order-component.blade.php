{{-- resources/views/livewire/admin/order-component.blade.php --}}
{{-- Styles: resources/css/blade.css (shared "" classes, Bootstrap 5 required) --}}
<div>

    {{-- ── Flash ── --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="material-icons-round">check_circle</i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif

    {{-- ── Top Bar ── --}}
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">All Orders</div>
        </div>
        <div class="topbar-right">
            <select class="select" wire:model.live="statusFilter">
                <option value="">All Status</option>
                <option value="new">New</option>
                <option value="preparing">Preparing</option>
                <option value="delivering">On the Way</option>
                <option value="completed">Completed</option>
            </select>
            <button class="export-btn" wire:click="export">
                <span class="material-icons-round">download</span>
                Export
            </button>
        </div>
    </div>

    {{-- ── Table Card ── --}}
    <div class="table-card">

        {{-- Toolbar --}}
        <div class="toolbar">
            <div class="search-wrap">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                       class="search-input"
                       placeholder="Search by order ID, customer or restaurant…"
                       wire:model.live.debounce.350ms="search">
            </div>
        </div>

        {{-- Table --}}
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Restaurant</th>
                        <th>Rider</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Commission</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    @php
                        $statusMap = [
                            'new'         => ['label' => 'New',        'class' => 'order-status-new'],
                            'preparing'   => ['label' => 'Preparing',  'class' => 'order-status-preparing'],
                            'delivering'  => ['label' => 'On the Way', 'class' => 'order-status-delivering'],
                            'completed'   => ['label' => 'Completed',  'class' => 'order-status-completed'],
                        ];
                        $statusInfo = $statusMap[$order->status] ?? ['label' => ucwords(str_replace('_', ' ', $order->status)), 'class' => 'order-status-default'];
                    @endphp
                    <tr wire:key="order-{{ $order->id }}">
                        <td class="order-id">#KK{{ $order->id }}</td>
                        <td class="order-customer">{{ $order->customer->name ?? '—' }}</td>
                        <td class="order-restaurant">{{ $order->restaurant->name ?? '—' }}</td>
                        <td class="order-rider">{{ $order->rider->name ?? '—' }}</td>
                        <td class="text-right order-total">৳{{ number_format($order->total_amount) }}</td>
                        <td class="text-right order-commission">৳{{ number_format($order->commission_amount) }}</td>
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

        {{-- Pagination --}}
        @if($orders->hasPages())
            <div class="pagination">
                <small>{{ $orders->firstItem() }}–{{ $orders->lastItem() }} / Total {{ number_format($orders->total()) }}</small>
                {{ $orders->links() }}
            </div>
        @endif

    </div>

    {{-- ── View Order Modal ── --}}
    @if($selectedOrder)
    <div class="dialog-backdrop" wire:click="closeModal">
        <div class="dialog" wire:click.stop>
            @php
                $statusMap = [
                    'new'         => ['label' => 'New',        'class' => 'order-status-new'],
                    'preparing'   => ['label' => 'Preparing',  'class' => 'order-status-preparing'],
                    'delivering'  => ['label' => 'On the Way', 'class' => 'order-status-delivering'],
                    'completed'   => ['label' => 'Completed',  'class' => 'order-status-completed'],
                ];
                $statusInfo = $statusMap[$selectedOrder->status] ?? ['label' => ucwords(str_replace('_', ' ', $selectedOrder->status)), 'class' => 'order-status-default'];
            @endphp

            <div class="dialog-header">
                <div class="dialog-title">Order #KK{{ $selectedOrder->id }}</div>
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
                        <div class="dialog-label">Restaurant</div>
                        <div class="dialog-value">{{ $selectedOrder->restaurant->name ?? '—' }}</div>
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

                @if($selectedOrder->delivery_address)
                <div class="dialog-field dialog-field-full">
                    <div class="dialog-label">Delivery Address</div>
                    <div class="dialog-value">{{ $selectedOrder->delivery_address }}</div>
                </div>
                @endif

                @if($selectedOrder->items && count($selectedOrder->items))
                <div class="dialog-items">
                    <div class="dialog-label">Items</div>
                    <div class="dialog-items-list">
                        @foreach($selectedOrder->items as $item)
                        <div class="dialog-item-row">
                            <span>{{ $item->quantity ?? 1 }}x {{ $item->name ?? ($item->product->name ?? 'Item') }}</span>
                            <span>৳{{ number_format($item->price ?? 0) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="dialog-summary">
                    <div class="dialog-summary-row">
                        <span>Total</span>
                        <span class="dialog-summary-value">৳{{ number_format($selectedOrder->total_amount) }}</span>
                    </div>
                    <div class="dialog-summary-row">
                        <span>Commission</span>
                        <span class="dialog-summary-value dialog-summary-pink">৳{{ number_format($selectedOrder->commission_amount) }}</span>
                    </div>
                </div>
            </div>

            <div class="dialog-footer">
                <button class="dialog-btn-close" wire:click="closeModal">Close</button>
            </div>
        </div>
    </div>
    @endif

</div>

