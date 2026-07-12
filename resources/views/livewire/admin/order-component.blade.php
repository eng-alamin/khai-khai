<div>

    {{-- ── Flash ── --}}
    @if(session('success'))
        <div class="ord-alert ord-alert-success">
            <span class="material-icons-round">check_circle</span>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="ord-alert-close">&times;</button>
        </div>
    @endif

    {{-- ── Top Bar ── --}}
    <div class="ord-topbar">
        <div class="ord-topbar-left">
            <div class="ord-topbar-title">All Orders</div>
        </div>
        <div class="ord-topbar-right">
            <select class="ord-select" wire:model.live="statusFilter">
                <option value="">All Status</option>
                <option value="new">New</option>
                <option value="preparing">Preparing</option>
                <option value="delivering">On the Way</option>
                <option value="completed">Completed</option>
            </select>
            <button class="ord-export-btn" wire:click="export">
                <span class="material-icons-round">download</span>
                Export
            </button>
        </div>
    </div>

    {{-- ── Table Card ── --}}
    <div class="ord-table-card">

        {{-- Toolbar --}}
        <div class="ord-toolbar">
            <div class="ord-search-wrap">
                <span class="material-icons-round ord-search-icon">search</span>
                <input type="text"
                       class="ord-search-input"
                       placeholder="Search by order ID, customer or restaurant…"
                       wire:model.live.debounce.350ms="search">
            </div>
        </div>

        {{-- Table --}}
        <div class="ord-table-wrap">
            <table class="ord-table">
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
                            'new'         => ['label' => 'New',        'class' => 'ord-status-new'],
                            'preparing'   => ['label' => 'Preparing',  'class' => 'ord-status-preparing'],
                            'delivering'  => ['label' => 'On the Way', 'class' => 'ord-status-delivering'],
                            'completed'   => ['label' => 'Completed',  'class' => 'ord-status-completed'],
                        ];
                        $statusInfo = $statusMap[$order->status] ?? ['label' => ucwords(str_replace('_', ' ', $order->status)), 'class' => 'ord-status-default'];
                    @endphp
                    <tr>
                        <td class="ord-id">#KK{{ $order->id }}</td>
                        <td class="ord-customer">{{ $order->customer->name ?? '—' }}</td>
                        <td class="ord-restaurant">{{ $order->restaurant->name ?? '—' }}</td>
                        <td class="ord-rider">{{ $order->rider->name ?? '—' }}</td>
                        <td class="text-right ord-total">৳{{ number_format($order->total_amount) }}</td>
                        <td class="text-right ord-commission">৳{{ number_format($order->commission_amount) }}</td>
                        <td class="text-center">
                            <span class="ord-status-badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                        </td>
                        <td class="text-center">
                            <button class="ord-view-btn" wire:click="viewOrder({{ $order->id }})">
                                <span class="material-icons-round">visibility</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="ord-empty">
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
            <div class="ord-pagination">
                <small>{{ $orders->firstItem() }}–{{ $orders->lastItem() }} / Total {{ number_format($orders->total()) }}</small>
                {{ $orders->links() }}
            </div>
        @endif

    </div>

    {{-- ── View Order Modal ── --}}
    @if($selectedOrder)
    <div class="ord-modal-backdrop" wire:click="closeModal">
        <div class="ord-modal" wire:click.stop>
            @php
                $statusMap = [
                    'new'         => ['label' => 'New',        'class' => 'ord-status-new'],
                    'preparing'   => ['label' => 'Preparing',  'class' => 'ord-status-preparing'],
                    'delivering'  => ['label' => 'On the Way', 'class' => 'ord-status-delivering'],
                    'completed'   => ['label' => 'Completed',  'class' => 'ord-status-completed'],
                ];
                $statusInfo = $statusMap[$selectedOrder->status] ?? ['label' => ucwords(str_replace('_', ' ', $selectedOrder->status)), 'class' => 'ord-status-default'];
            @endphp

            <div class="ord-modal-header">
                <div class="ord-modal-title">Order #KK{{ $selectedOrder->id }}</div>
                <button class="ord-modal-close" wire:click="closeModal">
                    <span class="material-icons-round">close</span>
                </button>
            </div>

            <div class="ord-modal-body">
                <div class="ord-modal-status-row">
                    <span class="ord-status-badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                    <span class="ord-modal-date">{{ $selectedOrder->created_at->format('j M Y, g:i A') }}</span>
                </div>

                <div class="ord-modal-grid">
                    <div class="ord-modal-field">
                        <div class="ord-modal-label">Customer</div>
                        <div class="ord-modal-value">{{ $selectedOrder->customer->name ?? '—' }}</div>
                        @if($selectedOrder->customer?->phone)
                            <div class="ord-modal-sub">{{ $selectedOrder->customer->phone }}</div>
                        @endif
                    </div>

                    <div class="ord-modal-field">
                        <div class="ord-modal-label">Restaurant</div>
                        <div class="ord-modal-value">{{ $selectedOrder->restaurant->name ?? '—' }}</div>
                    </div>

                    <div class="ord-modal-field">
                        <div class="ord-modal-label">Rider</div>
                        <div class="ord-modal-value">{{ $selectedOrder->rider->name ?? '—' }}</div>
                        @if($selectedOrder->rider?->phone)
                            <div class="ord-modal-sub">{{ $selectedOrder->rider->phone }}</div>
                        @endif
                    </div>

                    <div class="ord-modal-field">
                        <div class="ord-modal-label">Payment Method</div>
                        <div class="ord-modal-value">{{ \Illuminate\Support\Str::headline($selectedOrder->payment_method ?? '') ?: '—' }}</div>
                    </div>
                </div>

                @if($selectedOrder->delivery_address)
                <div class="ord-modal-field ord-modal-field-full">
                    <div class="ord-modal-label">Delivery Address</div>
                    <div class="ord-modal-value">{{ $selectedOrder->delivery_address }}</div>
                </div>
                @endif

                @if($selectedOrder->items && count($selectedOrder->items))
                <div class="ord-modal-items">
                    <div class="ord-modal-label">Items</div>
                    <div class="ord-modal-items-list">
                        @foreach($selectedOrder->items as $item)
                        <div class="ord-modal-item-row">
                            <span>{{ $item->quantity ?? 1 }}x {{ $item->name ?? ($item->product->name ?? 'Item') }}</span>
                            <span>৳{{ number_format($item->price ?? 0) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="ord-modal-summary">
                    <div class="ord-modal-summary-row">
                        <span>Total</span>
                        <span class="ord-modal-summary-value">৳{{ number_format($selectedOrder->total_amount) }}</span>
                    </div>
                    <div class="ord-modal-summary-row">
                        <span>Commission</span>
                        <span class="ord-modal-summary-value ord-modal-summary-pink">৳{{ number_format($selectedOrder->commission_amount) }}</span>
                    </div>
                </div>
            </div>

            <div class="ord-modal-footer">
                <button class="ord-modal-btn-close" wire:click="closeModal">Close</button>
            </div>
        </div>
    </div>
    @endif

</div>

@push('styles')
<style>
    .ord-topbar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 20px 20px 16px;
        position: sticky; top: 0; z-index: 50;
        background: var(--bg);
    }
    .ord-topbar-left { display: flex; align-items: center; gap: 10px; }
    .ord-topbar-title {
        font-size: 1.22rem; font-weight: 800; color: var(--dark);
        font-family: var(--font);
    }
    .ord-topbar-right { display: flex; align-items: center; gap: 10px; }
    .ord-select {
        padding: 8px 14px; border: 1.5px solid var(--border);
        border-radius: var(--radius-sm); font-family: var(--font);
        font-size: .8rem; color: var(--dark); background: var(--card-bg);
        outline: none; cursor: pointer; transition: var(--transition);
    }
    .ord-select:focus { border-color: var(--pink); }
    .ord-export-btn {
        display: inline-flex; align-items: center; gap: 5px;
        background: var(--card-bg); color: var(--soft-dark);
        border: 1.5px solid var(--border); border-radius: var(--radius-sm);
        padding: 8px 16px; font-size: .8rem; font-weight: 600;
        font-family: var(--font); cursor: pointer; transition: var(--transition);
    }
    .ord-export-btn:hover { border-color: var(--pink); color: var(--pink); }
    .ord-export-btn .material-icons-round { font-size: 1rem; }

    .ord-table-card {
        margin: 0 20px 30px;
        background: var(--card-bg); border-radius: var(--radius-lg);
        border: 1.5px solid var(--border); box-shadow: var(--shadow-card);
        overflow: hidden;
    }
    .ord-toolbar {
        display: flex; align-items: center; gap: 10px;
        padding: 14px 16px; border-bottom: 1px solid var(--border);
    }
    .ord-search-wrap { position: relative; flex: 1; }
    .ord-search-icon {
        position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
        color: var(--muted); font-size: 1rem; pointer-events: none;
    }
    .ord-search-input {
        width: 100%; padding: 8px 12px 8px 34px;
        border: 1.5px solid var(--border); border-radius: var(--radius-sm);
        font-family: var(--font); font-size: .82rem; color: var(--dark);
        background: var(--bg); outline: none; transition: var(--transition);
        box-sizing: border-box;
    }
    .ord-search-input:focus {
        border-color: var(--pink); box-shadow: 0 0 0 3px rgba(255,61,139,.1);
    }

    .ord-table-wrap { overflow-x: auto; }
    .ord-table { width: 100%; border-collapse: collapse; font-family: var(--font); }
    .ord-table thead tr {
        background: var(--bg); border-bottom: 1.5px solid var(--border);
    }
    .ord-table thead th {
        padding: 11px 16px; font-size: .72rem; font-weight: 700;
        color: var(--muted); text-transform: uppercase; letter-spacing: .05em;
        white-space: nowrap;
    }
    .ord-table thead th.text-center { text-align: center; }
    .ord-table thead th.text-right  { text-align: right; }
    .ord-table tbody tr { border-bottom: 1px solid var(--border); transition: background .12s; }
    .ord-table tbody tr:last-child { border-bottom: none; }
    .ord-table tbody tr:hover { background: var(--bg); }
    .ord-table tbody td {
        padding: 13px 16px; font-size: .84rem; color: var(--dark); vertical-align: middle;
    }
    .ord-table tbody td.text-center { text-align: center; }
    .ord-table tbody td.text-right  { text-align: right; }

    .ord-id          { font-weight: 700; color: var(--pink); font-size: .84rem; }
    .ord-customer    { font-weight: 600; color: var(--dark); }
    .ord-restaurant  { color: var(--dark); }
    .ord-rider       { color: var(--muted); }
    .ord-total       { font-weight: 700; color: var(--dark); }
    .ord-commission  { font-weight: 700; color: var(--pink); }

    .ord-status-badge {
        display: inline-block; padding: 4px 12px; border-radius: 50px;
        font-size: .72rem; font-weight: 700;
    }
    .ord-status-new        { background: rgba(59,130,246,.1);  color: #3b82f6; }
    .ord-status-preparing  { background: rgba(234,179,8,.12);  color: #b45309; }
    .ord-status-delivering { background: rgba(232,63,140,.1);  color: var(--pink); }
    .ord-status-completed  { background: rgba(34,197,94,.1);   color: #16a34a; }
    .ord-status-default    { background: rgba(107,114,128,.1); color: var(--muted); }

    .ord-view-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: var(--radius-sm);
        background: var(--bg); border: 1.5px solid var(--border);
        color: var(--soft-dark); cursor: pointer; transition: var(--transition);
    }
    .ord-view-btn:hover { border-color: var(--pink); color: var(--pink); }
    .ord-view-btn .material-icons-round { font-size: 1.1rem; }

    .ord-empty { text-align: center; padding: 60px 20px !important; color: var(--muted); }
    .ord-empty .material-icons-round {
        font-size: 2.8rem; opacity: .2; display: block; margin-bottom: 10px;
    }
    .ord-empty p { margin: 0; font-size: .88rem; }

    .ord-pagination {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 16px; border-top: 1px solid var(--border);
    }
    .ord-pagination small { font-size: .74rem; color: var(--muted); }

    .ord-alert {
        display: flex; align-items: center; gap: 8px;
        margin: 12px 20px; padding: 12px 14px;
        border-radius: var(--radius-md); font-size: .82rem;
    }
    .ord-alert span:nth-child(2) { flex: 1; }
    .ord-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
    .ord-alert-close { background: none; border: none; cursor: pointer; font-size: 1.1rem; color: inherit; padding: 0; }

    /* ── View Modal ── */
    .ord-modal-backdrop {
        position: fixed; inset: 0; z-index: 1000;
        background: rgba(20,20,30,.45);
        display: flex; align-items: center; justify-content: center;
        padding: 20px;
    }
    .ord-modal {
        width: 100%; max-width: 480px; max-height: 88vh;
        background: var(--card-bg); border-radius: var(--radius-lg);
        box-shadow: 0 20px 50px rgba(0,0,0,.2);
        display: flex; flex-direction: column; overflow: hidden;
        font-family: var(--font);
    }
    .ord-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 20px; border-bottom: 1px solid var(--border);
    }
    .ord-modal-title { font-size: 1.05rem; font-weight: 800; color: var(--dark); }
    .ord-modal-close {
        display: inline-flex; align-items: center; justify-content: center;
        width: 30px; height: 30px; border-radius: 50%;
        background: var(--bg); border: none; color: var(--muted);
        cursor: pointer; transition: var(--transition);
    }
    .ord-modal-close:hover { background: rgba(232,63,140,.1); color: var(--pink); }
    .ord-modal-close .material-icons-round { font-size: 1.15rem; }

    .ord-modal-body { padding: 20px; overflow-y: auto; }
    .ord-modal-status-row {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 18px;
    }
    .ord-modal-date { font-size: .78rem; color: var(--muted); }

    .ord-modal-grid {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 16px; margin-bottom: 16px;
    }
    .ord-modal-field-full { grid-column: 1 / -1; margin-bottom: 16px; }
    .ord-modal-label {
        font-size: .68rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px;
    }
    .ord-modal-value { font-size: .88rem; font-weight: 600; color: var(--dark); }
    .ord-modal-sub { font-size: .76rem; color: var(--muted); margin-top: 2px; }

    .ord-modal-items {
        border-top: 1px solid var(--border); padding-top: 14px; margin-bottom: 14px;
    }
    .ord-modal-items-list { margin-top: 8px; display: flex; flex-direction: column; gap: 6px; }
    .ord-modal-item-row {
        display: flex; align-items: center; justify-content: space-between;
        font-size: .84rem; color: var(--dark);
    }

    .ord-modal-summary {
        border-top: 1px solid var(--border); padding-top: 14px;
        display: flex; flex-direction: column; gap: 8px;
    }
    .ord-modal-summary-row {
        display: flex; align-items: center; justify-content: space-between;
        font-size: .86rem; color: var(--dark);
    }
    .ord-modal-summary-value { font-weight: 700; }
    .ord-modal-summary-pink { color: var(--pink); }

    .ord-modal-footer {
        padding: 14px 20px; border-top: 1px solid var(--border);
        display: flex; justify-content: flex-end;
    }
    .ord-modal-btn-close {
        padding: 8px 20px; border-radius: var(--radius-sm);
        background: var(--pink); color: #fff; border: none;
        font-family: var(--font); font-size: .82rem; font-weight: 700;
        cursor: pointer; transition: var(--transition);
    }
    .ord-modal-btn-close:hover { opacity: .9; }

    @media (max-width: 640px) {
        .ord-table-card { margin: 0 14px 20px; }
        .ord-topbar { padding: 14px 14px 10px; flex-wrap: wrap; gap: 10px; }
        .ord-topbar-right { width: 100%; }
        .ord-toolbar { flex-wrap: wrap; }
        .ord-modal-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush