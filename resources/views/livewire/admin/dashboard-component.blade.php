<div>

    {{-- ── Stat Cards ── --}}
    <div class="dash-stats-row">

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:rgba(232,63,140,.1)">
                <i class="material-icons-round" style="color:var(--pink)">shopping_bag</i>
            </div>
            <div class="dash-stat-body">
                <div class="dash-stat-value" style="color:var(--pink)">{{ $stats['orders_today'] }}</div>
                <div class="dash-stat-label">Total Orders Today</div>
                <div class="dash-stat-trend {{ $stats['order_growth'] >= 0 ? 'up' : 'down' }}">
                    <i class="material-icons-round">{{ $stats['order_growth'] >= 0 ? 'trending_up' : 'trending_down' }}</i>
                    {{ abs($stats['order_growth']) }}% {{ $stats['order_growth'] >= 0 ? 'increase' : 'decrease' }}
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:rgba(34,197,94,.1)">
                <i class="material-icons-round" style="color:#16a34a">show_chart</i>
            </div>
            <div class="dash-stat-body">
                <div class="dash-stat-value" style="color:#16a34a">৳{{ number_format($stats['revenue_today']) }}</div>
                <div class="dash-stat-label">Today's Revenue</div>
                <div class="dash-stat-trend {{ $stats['revenue_growth'] >= 0 ? 'up' : 'down' }}">
                    <i class="material-icons-round">{{ $stats['revenue_growth'] >= 0 ? 'trending_up' : 'trending_down' }}</i>
                    {{ abs($stats['revenue_growth']) }}% {{ $stats['revenue_growth'] >= 0 ? 'increase' : 'decrease' }}
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:rgba(234,179,8,.12)">
                <i class="material-icons-round" style="color:#b45309">storefront</i>
            </div>
            <div class="dash-stat-body">
                <div class="dash-stat-value" style="color:#b45309">{{ $stats['active_vendors'] }}</div>
                <div class="dash-stat-label">Active Vendors</div>
                <div class="dash-stat-trend up">
                    <i class="material-icons-round">trending_up</i>
                    {{ $stats['new_vendors_week'] }} new this week
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:rgba(59,130,246,.1)">
                <i class="material-icons-round" style="color:#3b82f6">sports_motorsports</i>
            </div>
            <div class="dash-stat-body">
                <div class="dash-stat-value" style="color:#3b82f6">{{ $stats['active_riders'] }}</div>
                <div class="dash-stat-label">Active Riders</div>
                <div class="dash-stat-trend neutral">
                    <i class="material-icons-round">radio_button_checked</i>
                    Live
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:rgba(99,102,241,.1)">
                <i class="material-icons-round" style="color:#6366f1">groups</i>
            </div>
            <div class="dash-stat-body">
                <div class="dash-stat-value" style="color:#6366f1">{{ $stats['total_customers'] }}</div>
                <div class="dash-stat-label">Total Customers</div>
                <div class="dash-stat-trend up">
                    <i class="material-icons-round">trending_up</i>
                    +{{ $stats['customers_growth'] }} this month
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:rgba(232,63,140,.1)">
                <i class="material-icons-round" style="color:var(--pink)">percent</i>
            </div>
            <div class="dash-stat-body">
                <div class="dash-stat-value" style="color:var(--pink)">৳{{ number_format($stats['commission_today']) }}</div>
                <div class="dash-stat-label">Today's Commission</div>
                <div class="dash-stat-trend {{ $stats['commission_growth'] >= 0 ? 'up' : 'down' }}">
                    <i class="material-icons-round">{{ $stats['commission_growth'] >= 0 ? 'trending_up' : 'trending_down' }}</i>
                    {{ abs($stats['commission_growth']) }}% {{ $stats['commission_growth'] >= 0 ? 'more' : 'less' }}
                </div>
            </div>
        </div>

    </div>

    {{-- ── Chart Row ── --}}
    <div class="chart-row">

        {{-- Weekly Orders --}}
        <div class="chart-card">
            <div class="chart-card-header">
                <div class="chart-title">Weekly Orders</div>
                <span class="chart-pill">This Week</span>
            </div>
            <div class="bar-chart">
                @foreach($weeklyOrders as $day)
                <div class="bar-col">
                    <div class="bar-track">
                        <div class="bar-fill" style="height: {{ max($day['percent'], 6) }}%"></div>
                    </div>
                    <div class="bar-label">{{ $day['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Orders By City --}}
        <div class="chart-card">
            <div class="chart-card-header">
                <div class="chart-title">Orders By City</div>
            </div>
            <div class="city-list">
                @forelse($ordersByCity as $city)
                <div class="city-row">
                    <div class="city-top">
                        <span class="city-name">{{ $city['city'] }}</span>
                        <span class="city-percent">{{ $city['percent'] }}%</span>
                    </div>
                    <div class="city-track">
                        <div class="city-fill" style="width: {{ $city['percent'] }}%"></div>
                    </div>
                </div>
                @empty
                <p class="chart-muted">No data available yet.</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ── Recent Activity ── --}}
    <div class="chart-card activity-card">
        <div class="chart-card-header">
            <div class="chart-title">Recent Activity</div>
        </div>
        <div class="activity-list">
            @forelse($recentActivity as $activity)
            <div class="activity-row">
                <div class="activity-icon activity-{{ $activity['color'] }}">
                    <i class="material-icons-round">{{ $activity['icon'] }}</i>
                </div>
                <div class="activity-text">{{ $activity['text'] }}</div>
                <div class="activity-time">{{ $activity['time_ago'] }}</div>
            </div>
            @empty
            <p class="chart-muted">No recent activity.</p>
            @endforelse
        </div>
    </div>

</div>
