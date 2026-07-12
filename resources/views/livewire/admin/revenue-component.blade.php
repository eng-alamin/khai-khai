<div>

    {{-- ── Flash ── --}}
    @if(session('success'))
        <div class="rev-alert rev-alert-success">
            <span class="material-icons-round">check_circle</span>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="rev-alert-close">&times;</button>
        </div>
    @endif

    {{-- ── Top Bar ── --}}
    <div class="rev-topbar">
        <div class="rev-topbar-left">
            <div class="rev-topbar-title">Revenue Management</div>
        </div>
    </div>

    {{-- ── Stats Row ── --}}
    <div class="rev-stats-row">

        <div class="rev-stat-card">
            <div class="rev-stat-icon" style="background:rgba(34,197,94,.1)">
                <span class="material-icons-round" style="color:#16a34a">insights</span>
            </div>
            <div class="rev-stat-body">
                <div class="rev-stat-value" style="color:#16a34a">৳{{ number_format($stats['total_revenue'], 0) }}</div>
                <div class="rev-stat-label">Total Revenue This Month</div>
            </div>
            <div class="rev-stat-bg-circle" style="background:rgba(34,197,94,.06)"></div>
        </div>

        <div class="rev-stat-card">
            <div class="rev-stat-icon" style="background:rgba(232,63,140,.1)">
                <span class="material-icons-round" style="color:var(--pink)">percent</span>
            </div>
            <div class="rev-stat-body">
                <div class="rev-stat-value" style="color:var(--pink)">৳{{ number_format($stats['commission'], 0) }}</div>
                <div class="rev-stat-label">KhaiKhai Commission</div>
            </div>
            <div class="rev-stat-bg-circle" style="background:rgba(232,63,140,.06)"></div>
        </div>

        <div class="rev-stat-card">
            <div class="rev-stat-icon" style="background:rgba(59,130,246,.1)">
                <span class="material-icons-round" style="color:#3b82f6">{{ $stats['growth'] >= 0 ? 'arrow_upward' : 'arrow_downward' }}</span>
            </div>
            <div class="rev-stat-body">
                <div class="rev-stat-value" style="color:#3b82f6">{{ $stats['growth'] >= 0 ? '+' : '' }}{{ $stats['growth'] }}%</div>
                <div class="rev-stat-label">Compared to Last Month</div>
            </div>
            <div class="rev-stat-bg-circle" style="background:rgba(59,130,246,.06)"></div>
        </div>

    </div>

    {{-- ── Trend Card ── --}}
    <div class="rev-trend-card">
        <div class="rev-trend-title">Monthly Revenue Trend</div>

        @forelse($trend as $row)
            <div class="rev-trend-row">
                <div class="rev-trend-top">
                    <span class="rev-trend-label">{{ $row['label'] }}</span>
                    <span class="rev-trend-value">৳{{ number_format($row['value'], 0) }}</span>
                </div>
                <div class="rev-trend-bar-track">
                    <div class="rev-trend-bar-fill" style="width: {{ $row['percent'] }}%"></div>
                </div>
            </div>
        @empty
            <div class="rev-empty">
                <span class="material-icons-round">bar_chart</span>
                <p>No revenue data found.</p>
            </div>
        @endforelse
    </div>

</div>

@push('styles')
<style>
    .rev-topbar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 20px 20px 16px;
        position: sticky; top: 0; z-index: 50;
        background: var(--bg);
    }
    .rev-topbar-left { display: flex; align-items: center; gap: 10px; }
    .rev-topbar-title {
        font-size: 1.22rem; font-weight: 800; color: var(--dark);
        font-family: var(--font);
    }

    .rev-stats-row {
        display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 14px; padding: 0 20px 18px;
    }
    .rev-stat-card {
        background: var(--card-bg); border-radius: var(--radius-lg);
        border: 1.5px solid var(--border); padding: 20px;
        display: flex; align-items: center; gap: 14px;
        position: relative; overflow: hidden;
        box-shadow: var(--shadow-card);
    }
    .rev-stat-icon {
        width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .rev-stat-icon .material-icons-round { font-size: 1.4rem; }
    .rev-stat-body { flex: 1; min-width: 0; }
    .rev-stat-value {
        font-size: 1.6rem; font-weight: 800; line-height: 1.1;
        font-family: var(--font);
    }
    .rev-stat-label {
        font-size: .75rem; color: var(--muted); margin-top: 3px;
        font-family: var(--font);
    }
    .rev-stat-bg-circle {
        position: absolute; right: -20px; top: 50%; transform: translateY(-50%);
        width: 90px; height: 90px; border-radius: 50%;
    }

    .rev-trend-card {
        margin: 0 20px 30px;
        background: var(--card-bg); border-radius: var(--radius-lg);
        border: 1.5px solid var(--border); box-shadow: var(--shadow-card);
        padding: 20px 22px 24px;
    }
    .rev-trend-title {
        font-size: 1rem; font-weight: 800; color: var(--dark);
        font-family: var(--font); margin-bottom: 18px;
    }
    .rev-trend-row + .rev-trend-row { margin-top: 18px; }
    .rev-trend-top {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 6px;
    }
    .rev-trend-label {
        font-size: .85rem; font-weight: 600; color: var(--dark);
        font-family: var(--font);
    }
    .rev-trend-value {
        font-size: .85rem; font-weight: 800; color: var(--pink);
        font-family: var(--font);
    }
    .rev-trend-bar-track {
        width: 100%; height: 6px; border-radius: 50px;
        background: rgba(232,63,140,.12); overflow: hidden;
    }
    .rev-trend-bar-fill {
        height: 100%; border-radius: 50px;
        background: var(--pink);
        transition: width .3s ease;
    }

    .rev-empty { text-align: center; padding: 40px 20px; color: var(--muted); }
    .rev-empty .material-icons-round {
        font-size: 2.8rem; opacity: .2; display: block; margin-bottom: 10px;
    }
    .rev-empty p { margin: 0; font-size: .88rem; }

    .rev-alert {
        display: flex; align-items: center; gap: 8px;
        margin: 12px 20px; padding: 12px 14px;
        border-radius: var(--radius-md); font-size: .82rem;
    }
    .rev-alert span:nth-child(2) { flex: 1; }
    .rev-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
    .rev-alert-close { background: none; border: none; cursor: pointer; font-size: 1.1rem; color: inherit; padding: 0; }

    @media (max-width: 640px) {
        .rev-stats-row { grid-template-columns: 1fr; padding: 0 14px 14px; }
        .rev-trend-card { margin: 0 14px 20px; }
        .rev-topbar { padding: 14px 14px 10px; }
    }
</style>
@endpush