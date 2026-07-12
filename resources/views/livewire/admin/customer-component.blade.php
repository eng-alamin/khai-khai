<div>

    {{-- ── Flash ── --}}
    @if(session('success'))
        <div class="cust-alert cust-alert-success">
            <span class="material-icons-round">check_circle</span>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="cust-alert-close">&times;</button>
        </div>
    @endif

    {{-- ── Top Bar ── --}}
    <div class="cust-topbar">
        <div class="cust-topbar-left">
            <div class="cust-topbar-title">Customer Management</div>
            <span class="cust-total-badge">{{ $stats['total'] }} Members</span>
        </div>
        <button class="cust-export-btn" wire:click="export">
            <span class="material-icons-round">download</span>
            Export
        </button>
    </div>

    {{-- ── Stats Row ── --}}
    <div class="cust-stats-row">

        <div class="cust-stat-card">
            <div class="cust-stat-icon" style="background:rgba(232,63,140,.1)">
                <span class="material-icons-round" style="color:var(--pink)">person_add</span>
            </div>
            <div class="cust-stat-body">
                <div class="cust-stat-value" style="color:var(--pink)">{{ $stats['new_this_month'] }}</div>
                <div class="cust-stat-label">New This Month</div>
            </div>
            <div class="cust-stat-bg-circle" style="background:rgba(232,63,140,.06)"></div>
        </div>

        <div class="cust-stat-card">
            <div class="cust-stat-icon" style="background:rgba(34,197,94,.1)">
                <span class="material-icons-round" style="color:#16a34a">refresh</span>
            </div>
            <div class="cust-stat-body">
                <div class="cust-stat-value" style="color:#16a34a">{{ $stats['repeat_rate'] }}%</div>
                <div class="cust-stat-label">Repeat Orders</div>
            </div>
            <div class="cust-stat-bg-circle" style="background:rgba(34,197,94,.06)"></div>
        </div>

        <div class="cust-stat-card">
            <div class="cust-stat-icon" style="background:rgba(99,102,241,.1)">
                <span class="material-icons-round" style="color:#6366f1">star</span>
            </div>
            <div class="cust-stat-body">
                <div class="cust-stat-value" style="color:#6366f1">৳{{ number_format($stats['avg_order_value']) }}</div>
                <div class="cust-stat-label">Avg. Order Value</div>
            </div>
            <div class="cust-stat-bg-circle" style="background:rgba(99,102,241,.06)"></div>
        </div>

    </div>

    {{-- ── Table Card ── --}}
    <div class="cust-table-card">

        {{-- Toolbar --}}
        <div class="cust-toolbar">
            <div class="cust-search-wrap">
                <span class="material-icons-round cust-search-icon">search</span>
                <input type="text"
                       class="cust-search-input"
                       placeholder="Search by name, phone or email…"
                       wire:model.live.debounce.350ms="search">
            </div>
            <select class="cust-select" wire:model.live="sortBy">
                <option value="total_orders">Most Orders</option>
                <option value="total_spent">Most Spent</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="cust-table-wrap">
            <table class="cust-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Area</th>
                        <th class="text-center">Total Orders</th>
                        <th class="text-right">Total Spent</th>
                        <th class="text-right">Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td>
                            <div class="cust-name-cell">
                                <div class="cust-avatar">
                                    {{ strtoupper(mb_substr($customer->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="cust-name">{{ $customer->name }}</div>
                                    @if($customer->email)
                                        <div class="cust-email">{{ $customer->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="cust-phone">{{ $customer->phone ?? '—' }}</td>
                        <td>
                            @php
                                $area = $customer->customerProfile?->defaultAddress?->city ?? null;
                            @endphp
                            @if($area)
                                <span class="cust-area-badge">{{ $area }}</span>
                            @else
                                <span class="cust-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="cust-order-badge">{{ $customer->total_orders ?? 0 }}</span>
                        </td>
                        <td class="text-right cust-spent">
                            ৳{{ number_format(intdiv((int)($customer->total_spent ?? 0), 100)) }}
                        </td>
                        <td class="text-right cust-joined">
                            {{ $customer->created_at->format('j M Y') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="cust-empty">
                            <span class="material-icons-round">group_off</span>
                            <p>No customers found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($customers->hasPages())
            <div class="cust-pagination">
                <small>{{ $customers->firstItem() }}–{{ $customers->lastItem() }} / Total {{ number_format($customers->total()) }}</small>
                {{ $customers->links() }}
            </div>
        @endif

    </div>

</div>

@push('styles')
<style>
    .cust-topbar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 20px 20px 16px;
        position: sticky; top: 0; z-index: 50;
        background: var(--bg);
    }
    .cust-topbar-left { display: flex; align-items: center; gap: 10px; }
    .cust-topbar-title {
        font-size: 1.22rem; font-weight: 800; color: var(--dark);
        font-family: var(--font);
    }
    .cust-total-badge {
        background: var(--pink); color: #fff;
        font-size: .72rem; font-weight: 700;
        padding: 3px 10px; border-radius: 50px;
        font-family: var(--font);
    }
    .cust-export-btn {
        display: inline-flex; align-items: center; gap: 5px;
        background: var(--card-bg); color: var(--soft-dark);
        border: 1.5px solid var(--border); border-radius: var(--radius-sm);
        padding: 8px 16px; font-size: .8rem; font-weight: 600;
        font-family: var(--font); cursor: pointer; transition: var(--transition);
    }
    .cust-export-btn:hover { border-color: var(--pink); color: var(--pink); }
    .cust-export-btn .material-icons-round { font-size: 1rem; }

    .cust-stats-row {
        display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 14px; padding: 0 20px 18px;
    }
    .cust-stat-card {
        background: var(--card-bg); border-radius: var(--radius-lg);
        border: 1.5px solid var(--border); padding: 20px;
        display: flex; align-items: center; gap: 14px;
        position: relative; overflow: hidden;
        box-shadow: var(--shadow-card);
    }
    .cust-stat-icon {
        width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .cust-stat-icon .material-icons-round { font-size: 1.4rem; }
    .cust-stat-body { flex: 1; min-width: 0; }
    .cust-stat-value {
        font-size: 1.6rem; font-weight: 800; line-height: 1.1;
        font-family: var(--font);
    }
    .cust-stat-label {
        font-size: .75rem; color: var(--muted); margin-top: 3px;
        font-family: var(--font);
    }
    .cust-stat-bg-circle {
        position: absolute; right: -20px; top: 50%; transform: translateY(-50%);
        width: 90px; height: 90px; border-radius: 50%;
    }

    .cust-table-card {
        margin: 0 20px 30px;
        background: var(--card-bg); border-radius: var(--radius-lg);
        border: 1.5px solid var(--border); box-shadow: var(--shadow-card);
        overflow: hidden;
    }
    .cust-toolbar {
        display: flex; align-items: center; gap: 10px;
        padding: 14px 16px; border-bottom: 1px solid var(--border);
    }
    .cust-search-wrap { position: relative; flex: 1; }
    .cust-search-icon {
        position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
        color: var(--muted); font-size: 1rem; pointer-events: none;
    }
    .cust-search-input {
        width: 100%; padding: 8px 12px 8px 34px;
        border: 1.5px solid var(--border); border-radius: var(--radius-sm);
        font-family: var(--font); font-size: .82rem; color: var(--dark);
        background: var(--bg); outline: none; transition: var(--transition);
        box-sizing: border-box;
    }
    .cust-search-input:focus {
        border-color: var(--pink); box-shadow: 0 0 0 3px rgba(255,61,139,.1);
    }
    .cust-select {
        padding: 8px 12px; border: 1.5px solid var(--border);
        border-radius: var(--radius-sm); font-family: var(--font);
        font-size: .8rem; color: var(--dark); background: var(--bg);
        outline: none; cursor: pointer; transition: var(--transition);
    }
    .cust-select:focus { border-color: var(--pink); }

    .cust-table-wrap { overflow-x: auto; }
    .cust-table { width: 100%; border-collapse: collapse; font-family: var(--font); }
    .cust-table thead tr {
        background: var(--bg); border-bottom: 1.5px solid var(--border);
    }
    .cust-table thead th {
        padding: 11px 16px; font-size: .72rem; font-weight: 700;
        color: var(--muted); text-transform: uppercase; letter-spacing: .05em;
        white-space: nowrap;
    }
    .cust-table thead th.text-center { text-align: center; }
    .cust-table thead th.text-right  { text-align: right; }
    .cust-table tbody tr { border-bottom: 1px solid var(--border); transition: background .12s; }
    .cust-table tbody tr:last-child { border-bottom: none; }
    .cust-table tbody tr:hover { background: var(--bg); }
    .cust-table tbody td {
        padding: 13px 16px; font-size: .84rem; color: var(--dark); vertical-align: middle;
    }
    .cust-table tbody td.text-center { text-align: center; }
    .cust-table tbody td.text-right  { text-align: right; }

    .cust-name-cell { display: flex; align-items: center; gap: 10px; }
    .cust-avatar {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        background: rgba(232,63,140,.12); color: var(--pink);
        font-size: .88rem; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
    }
    .cust-name  { font-weight: 700; font-size: .88rem; color: var(--dark); }
    .cust-email { font-size: .72rem; color: var(--muted); margin-top: 1px; }
    .cust-phone { color: var(--muted); font-size: .82rem; }
    .cust-area-badge {
        display: inline-block; padding: 3px 10px; border-radius: 50px;
        background: rgba(99,102,241,.1); color: #6366f1;
        font-size: .72rem; font-weight: 600;
    }
    .cust-muted { color: var(--muted); }
    .cust-order-badge {
        display: inline-block; padding: 4px 12px; border-radius: 50px;
        background: rgba(99,102,241,.12); color: #6366f1;
        font-size: .78rem; font-weight: 700;
    }
    .cust-spent  { font-weight: 700; color: #16a34a; font-size: .88rem; }
    .cust-joined { color: var(--muted); font-size: .78rem; }

    .cust-empty { text-align: center; padding: 60px 20px !important; color: var(--muted); }
    .cust-empty .material-icons-round {
        font-size: 2.8rem; opacity: .2; display: block; margin-bottom: 10px;
    }
    .cust-empty p { margin: 0; font-size: .88rem; }

    .cust-pagination {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 16px; border-top: 1px solid var(--border);
    }
    .cust-pagination small { font-size: .74rem; color: var(--muted); }

    .cust-alert {
        display: flex; align-items: center; gap: 8px;
        margin: 12px 20px; padding: 12px 14px;
        border-radius: var(--radius-md); font-size: .82rem;
    }
    .cust-alert span:nth-child(2) { flex: 1; }
    .cust-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
    .cust-alert-close { background: none; border: none; cursor: pointer; font-size: 1.1rem; color: inherit; padding: 0; }

    @media (max-width: 640px) {
        .cust-stats-row { grid-template-columns: 1fr; padding: 0 14px 14px; }
        .cust-table-card { margin: 0 14px 20px; }
        .cust-topbar { padding: 14px 14px 10px; }
        .cust-toolbar { flex-wrap: wrap; }
    }
</style>
@endpush