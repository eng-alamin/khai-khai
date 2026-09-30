{{-- resources/views/livewire/admin/customer-component.blade.php --}}
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
            <div class="topbar-title">Customer Management</div>
            <span class="total-badge">{{ $stats['total'] }} Members</span>
        </div>
        <button class="export-btn" wire:click="export">
            <span class="material-icons-round">download</span>
            Export
        </button>
    </div>

    {{-- ── Stats Row ── --}}
    <div class="stats-row">

        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(232,63,140,.1)">
                <span class="material-icons-round" style="color:var(--pink)">person_add</span>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:var(--pink)">{{ $stats['new_this_month'] }}</div>
                <div class="stat-label">New This Month</div>
            </div>
            <div class="stat-bg-circle" style="background:rgba(232,63,140,.06)"></div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(34,197,94,.1)">
                <span class="material-icons-round" style="color:#16a34a">refresh</span>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:#16a34a">{{ $stats['repeat_rate'] }}%</div>
                <div class="stat-label">Repeat Orders</div>
            </div>
            <div class="stat-bg-circle" style="background:rgba(34,197,94,.06)"></div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(99,102,241,.1)">
                <span class="material-icons-round" style="color:#6366f1">star</span>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:#6366f1">৳{{ number_format($stats['avg_order_value']) }}</div>
                <div class="stat-label">Avg. Order Value</div>
            </div>
            <div class="stat-bg-circle" style="background:rgba(99,102,241,.06)"></div>
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
                       placeholder="Search by name, phone or email…"
                       wire:model.live.debounce.350ms="search">
            </div>
            <select class="select" wire:model.live="sortBy">
                <option value="total_orders">Most Orders</option>
                <option value="total_spent">Most Spent</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="table-wrap">
            <table class="table">
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
                            <div class="name-cell">
                                <div class="avatar">
                                    {{ strtoupper(mb_substr($customer->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="name">{{ $customer->name }}</div>
                                    @if($customer->email)
                                        <div class="email">{{ $customer->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="phone">{{ $customer->phone ?? '—' }}</td>
                        <td>
                            @php
                                $area = $customer->customerProfile?->defaultAddress?->city ?? null;
                            @endphp
                            @if($area)
                                <span class="area-badge">{{ $area }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="order-badge">{{ $customer->total_orders ?? 0 }}</span>
                        </td>
                        <td class="text-right spent">
                            ৳{{ number_format(intdiv((int)($customer->total_spent ?? 0), 100)) }}
                        </td>
                        <td class="text-right joined">
                            {{ $customer->created_at->format('j M Y') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="empty">
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
            <div class="pagination">
                <small>{{ $customers->firstItem() }}–{{ $customers->lastItem() }} / Total {{ number_format($customers->total()) }}</small>
                {{ $customers->links() }}
            </div>
        @endif

    </div>

</div>

