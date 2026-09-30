<div>

    {{-- ── Flash ── --}}
    @if(session('success'))
        <div class="alert alert-success">
            <span class="material-icons-round">check_circle</span>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif

    {{-- ── Top Bar ── --}}
    <div class="topbar">
        <div class="topbar-title">Revenue Management</div>
    </div>

    {{-- ── Stats Row ── --}}
    <div class="stats-row">

        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(34,197,94,.1)">
                <i class="material-icons-round" style="color:#16a34a">insights</i>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:#16a34a">৳{{ number_format($stats['total_revenue'], 0) }}</div>
                <div class="stat-label">Total Revenue This Month</div>
            </div>
            <div class="stat-bg-circle" style="background:rgba(34,197,94,.06)"></div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(232,63,140,.1)">
                <i class="material-icons-round" style="color:var(--pink)">percent</i>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:var(--pink)">৳{{ number_format($stats['commission'], 0) }}</div>
                <div class="stat-label">KhaiKhai Commission</div>
            </div>
            <div class="stat-bg-circle" style="background:rgba(232,63,140,.06)"></div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(59,130,246,.1)">
                <i class="material-icons-round" style="color:#3b82f6">{{ $stats['growth'] >= 0 ? 'arrow_upward' : 'arrow_downward' }}</i>
            </div>
            <div class="stat-body">
                <div class="stat-value" style="color:#3b82f6">{{ $stats['growth'] >= 0 ? '+' : '' }}{{ $stats['growth'] }}%</div>
                <div class="stat-label">Compared to Last Month</div>
            </div>
            <div class="stat-bg-circle" style="background:rgba(59,130,246,.06)"></div>
        </div>

    </div>

    {{-- ── Trend Card ── --}}
    <div class="trend-card">
        <div class="trend-title">Monthly Revenue Trend</div>

        @forelse($trend as $row)
            <div class="trend-row">
                <div class="trend-top">
                    <span class="trend-label">{{ $row['label'] }}</span>
                    <span class="trend-value">৳{{ number_format($row['value'], 0) }}</span>
                </div>
                <div class="trend-bar-track">
                    <div class="trend-bar-fill" style="width: {{ $row['percent'] }}%"></div>
                </div>
            </div>
        @empty
            <div class="empty">
                <i class="material-icons-round empty-icon">bar_chart</i>
                <p>No revenue data found.</p>
            </div>
        @endforelse
    </div>

</div>
