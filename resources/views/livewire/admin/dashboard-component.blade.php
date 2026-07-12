<div>

    {{-- ── Stat Cards ── --}}
    <div class="dash-stats-row">

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:rgba(232,63,140,.1)">
                <span class="material-icons-round" style="color:var(--pink)">shopping_bag</span>
            </div>
            <div class="dash-stat-body">
                <div class="dash-stat-value" style="color:var(--pink)">{{ $stats['orders_today'] }}</div>
                <div class="dash-stat-label">Total Orders Today</div>
                <div class="dash-stat-trend {{ $stats['order_growth'] >= 0 ? 'up' : 'down' }}">
                    <span class="material-icons-round">{{ $stats['order_growth'] >= 0 ? 'trending_up' : 'trending_down' }}</span>
                    {{ abs($stats['order_growth']) }}% {{ $stats['order_growth'] >= 0 ? 'increase' : 'decrease' }}
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:rgba(34,197,94,.1)">
                <span class="material-icons-round" style="color:#16a34a">show_chart</span>
            </div>
            <div class="dash-stat-body">
                <div class="dash-stat-value" style="color:#16a34a">৳{{ number_format($stats['revenue_today']) }}</div>
                <div class="dash-stat-label">Today's Revenue</div>
                <div class="dash-stat-trend {{ $stats['revenue_growth'] >= 0 ? 'up' : 'down' }}">
                    <span class="material-icons-round">{{ $stats['revenue_growth'] >= 0 ? 'trending_up' : 'trending_down' }}</span>
                    {{ abs($stats['revenue_growth']) }}% {{ $stats['revenue_growth'] >= 0 ? 'increase' : 'decrease' }}
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:rgba(234,179,8,.12)">
                <span class="material-icons-round" style="color:#b45309">storefront</span>
            </div>
            <div class="dash-stat-body">
                <div class="dash-stat-value" style="color:#b45309">{{ $stats['active_vendors'] }}</div>
                <div class="dash-stat-label">Active Vendors</div>
                <div class="dash-stat-trend up">
                    <span class="material-icons-round">trending_up</span>
                    {{ $stats['new_vendors_week'] }} new this week
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:rgba(59,130,246,.1)">
                <span class="material-icons-round" style="color:#3b82f6">sports_motorsports</span>
            </div>
            <div class="dash-stat-body">
                <div class="dash-stat-value" style="color:#3b82f6">{{ $stats['active_riders'] }}</div>
                <div class="dash-stat-label">Active Riders</div>
                <div class="dash-stat-trend neutral">
                    <span class="material-icons-round">radio_button_checked</span>
                    Live
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:rgba(99,102,241,.1)">
                <span class="material-icons-round" style="color:#6366f1">groups</span>
            </div>
            <div class="dash-stat-body">
                <div class="dash-stat-value" style="color:#6366f1">{{ $stats['total_customers'] }}</div>
                <div class="dash-stat-label">Total Customers</div>
                <div class="dash-stat-trend up">
                    <span class="material-icons-round">trending_up</span>
                    +{{ $stats['customers_growth'] }} this month
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:rgba(232,63,140,.1)">
                <span class="material-icons-round" style="color:var(--pink)">percent</span>
            </div>
            <div class="dash-stat-body">
                <div class="dash-stat-value" style="color:var(--pink)">৳{{ number_format($stats['commission_today']) }}</div>
                <div class="dash-stat-label">Today's Commission</div>
                <div class="dash-stat-trend {{ $stats['commission_growth'] >= 0 ? 'up' : 'down' }}">
                    <span class="material-icons-round">{{ $stats['commission_growth'] >= 0 ? 'trending_up' : 'trending_down' }}</span>
                    {{ abs($stats['commission_growth']) }}% {{ $stats['commission_growth'] >= 0 ? 'more' : 'less' }}
                </div>
            </div>
        </div>

    </div>

    {{-- ── Chart Row ── --}}
    <div class="dash-chart-row">

        {{-- Weekly Orders --}}
        <div class="dash-card dash-chart-card">
            <div class="dash-card-header">
                <div class="dash-card-title">Weekly Orders</div>
                <span class="dash-pill">This Week</span>
            </div>
            <div class="dash-bar-chart">
                @foreach($weeklyOrders as $day)
                <div class="dash-bar-col">
                    <div class="dash-bar-track">
                        <div class="dash-bar-fill" style="height: {{ max($day['percent'], 6) }}%"></div>
                    </div>
                    <div class="dash-bar-label">{{ $day['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Orders By City --}}
        <div class="dash-card dash-city-card">
            <div class="dash-card-header">
                <div class="dash-card-title">Orders By City</div>
            </div>
            <div class="dash-city-list">
                @forelse($ordersByCity as $city)
                <div class="dash-city-row">
                    <div class="dash-city-top">
                        <span class="dash-city-name">{{ $city['city'] }}</span>
                        <span class="dash-city-percent">{{ $city['percent'] }}%</span>
                    </div>
                    <div class="dash-city-track">
                        <div class="dash-city-fill" style="width: {{ $city['percent'] }}%"></div>
                    </div>
                </div>
                @empty
                <p class="dash-muted-text">No data available yet.</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ── Recent Activity ── --}}
    <div class="dash-card dash-activity-card">
        <div class="dash-card-header">
            <div class="dash-card-title">Recent Activity</div>
        </div>
        <div class="dash-activity-list">
            @forelse($recentActivity as $activity)
            <div class="dash-activity-row">
                <div class="dash-activity-icon dash-activity-{{ $activity['color'] }}">
                    <span class="material-icons-round">{{ $activity['icon'] }}</span>
                </div>
                <div class="dash-activity-text">{{ $activity['text'] }}</div>
                <div class="dash-activity-time">{{ $activity['time_ago'] }}</div>
            </div>
            @empty
            <p class="dash-muted-text">No recent activity.</p>
            @endforelse
        </div>
    </div>

</div>

@push('styles')
<style>
    /* ── Stat Cards ── */
    .dash-stats-row {
        display: grid; grid-template-columns: repeat(6, 1fr);
        gap: 14px; padding: 20px 20px 18px;
    }
    .dash-stat-card {
        background: var(--card-bg); border-radius: var(--radius-lg);
        border: 1.5px solid var(--border); padding: 18px;
        display: flex; align-items: flex-start; gap: 12px;
        position: relative; overflow: hidden;
        box-shadow: var(--shadow-card);
    }
    .dash-stat-icon {
        width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .dash-stat-icon .material-icons-round { font-size: 1.25rem; }
    .dash-stat-body { flex: 1; min-width: 0; }
    .dash-stat-value {
        font-size: 1.3rem; font-weight: 800; line-height: 1.15;
        font-family: var(--font); white-space: nowrap;
    }
    .dash-stat-label {
        font-size: .72rem; color: var(--muted); margin-top: 3px;
        font-family: var(--font);
    }
    .dash-stat-trend {
        display: inline-flex; align-items: center; gap: 3px;
        font-size: .68rem; font-weight: 700; margin-top: 6px;
    }
    .dash-stat-trend .material-icons-round { font-size: .9rem; }
    .dash-stat-trend.up   { color: #16a34a; }
    .dash-stat-trend.down { color: #dc2626; }
    .dash-stat-trend.neutral { color: #3b82f6; }

    /* ── Shared Card ── */
    .dash-card {
        background: var(--card-bg); border-radius: var(--radius-lg);
        border: 1.5px solid var(--border); box-shadow: var(--shadow-card);
        padding: 20px;
    }
    .dash-card-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 18px;
    }
    .dash-card-title {
        font-size: 1rem; font-weight: 800; color: var(--dark);
        font-family: var(--font);
    }
    .dash-pill {
        background: rgba(232,63,140,.1); color: var(--pink);
        font-size: .7rem; font-weight: 700;
        padding: 4px 10px; border-radius: 50px;
        font-family: var(--font);
    }
    .dash-muted-text { color: var(--muted); font-size: .84rem; }

    /* ── Chart Row ── */
    .dash-chart-row {
        display: grid; grid-template-columns: 1.3fr 1fr;
        gap: 14px; padding: 0 20px 18px;
    }

    /* Weekly bar chart */
    .dash-bar-chart {
        display: flex; align-items: flex-end; justify-content: space-between;
        gap: 10px; height: 180px;
    }
    .dash-bar-col {
        flex: 1; display: flex; flex-direction: column; align-items: center;
        height: 100%; justify-content: flex-end;
    }
    .dash-bar-track {
        width: 100%; max-width: 40px; height: 100%;
        display: flex; align-items: flex-end;
    }
    .dash-bar-fill {
        width: 100%; background: var(--pink); border-radius: 8px 8px 0 0;
        transition: height .3s ease;
    }
    .dash-bar-label {
        font-size: .72rem; color: var(--muted); margin-top: 8px;
        font-family: var(--font);
    }

    /* City breakdown */
    .dash-city-list { display: flex; flex-direction: column; gap: 16px; }
    .dash-city-row { display: flex; flex-direction: column; gap: 6px; }
    .dash-city-top { display: flex; align-items: center; justify-content: space-between; }
    .dash-city-name { font-size: .84rem; font-weight: 700; color: var(--dark); }
    .dash-city-percent { font-size: .84rem; font-weight: 700; color: var(--pink); }
    .dash-city-track {
        width: 100%; height: 7px; border-radius: 50px;
        background: rgba(232,63,140,.08); overflow: hidden;
    }
    .dash-city-fill {
        height: 100%; background: var(--pink); border-radius: 50px;
        transition: width .3s ease;
    }

    /* ── Recent Activity ── */
    .dash-activity-card { margin: 0 20px 30px; }
    .dash-activity-list { display: flex; flex-direction: column; }
    .dash-activity-row {
        display: flex; align-items: center; gap: 14px;
        padding: 13px 0; border-bottom: 1px solid var(--border);
    }
    .dash-activity-row:last-child { border-bottom: none; padding-bottom: 0; }
    .dash-activity-row:first-child { padding-top: 0; }
    .dash-activity-icon {
        width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .dash-activity-icon .material-icons-round { font-size: 1.05rem; }
    .dash-activity-green  { background: rgba(34,197,94,.1);  color: #16a34a; }
    .dash-activity-blue   { background: rgba(59,130,246,.1); color: #3b82f6; }
    .dash-activity-orange { background: rgba(234,179,8,.12); color: #b45309; }
    .dash-activity-pink   { background: rgba(232,63,140,.1); color: var(--pink); }
    .dash-activity-text {
        flex: 1; min-width: 0; font-size: .84rem; color: var(--dark);
        font-family: var(--font);
    }
    .dash-activity-time {
        font-size: .72rem; color: var(--muted); white-space: nowrap; flex-shrink: 0;
    }

    /* ── Responsive ── */
    @media (max-width: 1200px) {
        .dash-stats-row { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 900px) {
        .dash-chart-row { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .dash-stats-row { grid-template-columns: repeat(2, 1fr); padding: 14px 14px 14px; gap: 10px; }
        .dash-stat-card { padding: 14px; }
        .dash-stat-value { font-size: 1.1rem; }
        .dash-chart-row { padding: 0 14px 14px; gap: 10px; }
        .dash-card { padding: 16px; }
        .dash-activity-card { margin: 0 14px 20px; }
        .dash-bar-chart { height: 140px; }
        .dash-activity-time { display: none; }
    }
    @media (max-width: 420px) {
        .dash-stats-row { grid-template-columns: 1fr; }
    }
</style>
@endpush