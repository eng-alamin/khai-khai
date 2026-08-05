<div>

    <!-- ══ KPI CARDS ══ -->
    <div class="row g-3 mb-3">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon-wrap ic-pink"><span class="material-icons-round">shopping_bag</span></div>
          <div class="stat-label">Today's Orders</div>
          <div class="stat-value mono">{{ $todayOrdersCount }}</div>
          <div class="stat-change {{ $ordersChangePercent >= 0 ? 'stat-up' : 'stat-down' }}">
            <span class="material-icons-round">{{ $ordersChangePercent >= 0 ? 'arrow_upward' : 'arrow_downward' }}</span>
            {{ $ordersChangePercent >= 0 ? '+' : '' }}{{ $ordersChangePercent }}% from yesterday
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon-wrap ic-green"><span class="material-icons-round">payments</span></div>
          <div class="stat-label">Today's Sales</div>
          <div class="stat-value mono">৳{{ number_format($todaySales) }}</div>
          <div class="stat-change {{ $salesChangePercent >= 0 ? 'stat-up' : 'stat-down' }}">
            <span class="material-icons-round">{{ $salesChangePercent >= 0 ? 'arrow_upward' : 'arrow_downward' }}</span>
            {{ $salesChangePercent >= 0 ? '+' : '' }}{{ $salesChangePercent }}%
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon-wrap ic-orange"><span class="material-icons-round">schedule</span></div>
          <div class="stat-label">Avg. Prep Time</div>
          <div class="stat-value mono">{{ $avgPrepMinutes }} min</div>
          <div class="stat-change stat-up"><span class="material-icons-round">arrow_upward</span>{{ $prepTimeNote }}</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon-wrap ic-blue"><span class="material-icons-round">star_rate</span></div>
          <div class="stat-label">Rating</div>
          <div class="stat-value mono">{{ number_format($avgRating, 1) }}★</div>
          <div class="stat-change stat-up"><span class="material-icons-round">emoji_events</span>{{ $totalReviews }} reviews</div>
        </div>
      </div>
    </div>

    <!-- ══ FEATURED FOOD BANNER ══ -->
    <div class="hero-banner mb-3">
      <img src="{{ $restaurant->banner_url ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=1400&q=80' }}" alt="Restaurant Banner"/>
      <div class="hero-overlay">
        <div class="hero-content">
          <h1>{{ $restaurant->name }}<br/><span>{{ $restaurant->category }}</span></h1>
          <p>{{ $todayOrdersCount }} orders completed today — you're doing great! 🎉</p>
        </div>
      </div>
    </div>

    <!-- ══ CHART + LIVE ORDERS ══ -->
    <div class="row g-3 mb-3">
      <div class="col-12 col-lg-7">
        <div class="dash-card h-100">
          <div class="dash-card-header grad-dark">
            <h6>Weekly Sales</h6>
            <p>Day-by-day sales comparison for this week</p>
          </div>
          <div class="dash-card-body" wire:ignore><canvas id="vendorChart" height="160"></canvas></div>
        </div>
      </div>
      <div class="col-12 col-lg-5">
        <div class="dash-card h-100">
          <div class="dash-card-header grad-pink">
            <h6>🔴 Live Orders</h6>
            <p><span class="live-dot"></span>{{ $liveOrdersPendingCount }} waiting</p>
          </div>
          <div class="dash-card-body p-0">
            <div id="liveOrdersList">
                @forelse ($liveOrders as $order)
                    <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;">
                      <div style="flex:1;">
                        <div style="font-size:.75rem;font-weight:700;color:var(--pink);">#{{ $order['number'] }}</div>
                        <div style="font-size:.8rem;font-weight:600;">{{ $order['customer'] }} — {{ $order['summary'] }}</div>
                        <div style="font-size:.7rem;color:var(--muted);">{{ $order['timeAgo'] }} • ৳{{ $order['total'] }}</div>
                      </div>
                      <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end;">
                        <span class="status-badge status-{{ $order['status'] === 'pending' ? 'pend' : 'in' }}" style="padding:2px 8px;font-size:.65rem;">
                            <span class="status-dot"></span>{{ $order['statusText'] }}
                        </span>
                      </div>
                    </div>
                @empty
                    <div class="text-center py-4" style="color:var(--muted);font-size:.85rem;">No live orders right now.</div>
                @endforelse
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ TOP MENU ITEMS (overview only) ══ -->
    <div class="d-flex align-items-center justify-content-between mb-3">
      <span style="font-size:.95rem;font-weight:800;">🍽️ Top Selling Items</span>
      <span style="font-size:.75rem;color:var(--muted);">Last 30 days</span>
    </div>
    <div class="row g-3 mb-3">
      @forelse ($topItems as $item)
        <div class="col-6 col-md-4 col-lg-2">
          <div class="food-card" style="position:relative;">
            <span class="food-card-badge">{{ $item['sold'] }} sold</span>
            <img src="{{ $item['image'] ?: 'https://placehold.co/400x300?text=Food' }}" class="food-card-img" alt="{{ $item['name'] }}"/>
            <div class="food-card-body">
              <div class="food-card-name">{{ $item['name'] }}</div>
              <div class="food-card-price">৳{{ $item['price'] }}</div>
              <div class="kk-progress mt-1"><div class="kk-progress-bar" style="width:{{ $item['progress'] }}%"></div></div>
            </div>
          </div>
        </div>
      @empty
        <div class="col-12">
            <div class="text-center py-4" style="color:var(--muted);font-size:.85rem;">No sales data yet in the last 30 days.</div>
        </div>
      @endforelse
    </div>

    <!-- ══ MENU OVERVIEW (read-only stats, no CRUD) ══ -->
    <div class="row g-3 mb-3">
      <div class="col-4">
        <div class="stat-card">
          <div class="stat-icon-wrap ic-blue"><span class="material-icons-round">restaurant_menu</span></div>
          <div class="stat-label">Total Menu Items</div>
          <div class="stat-value mono">{{ $totalMenuItems }}</div>
        </div>
      </div>
      <div class="col-4">
        <div class="stat-card">
          <div class="stat-icon-wrap ic-green"><span class="material-icons-round">check_circle</span></div>
          <div class="stat-label">Active</div>
          <div class="stat-value mono">{{ $activeMenuItems }}</div>
        </div>
      </div>
      <div class="col-4">
        <div class="stat-card">
          <div class="stat-icon-wrap ic-orange"><span class="material-icons-round">pause_circle</span></div>
          <div class="stat-label">Inactive</div>
          <div class="stat-value mono">{{ $inactiveMenuItems }}</div>
        </div>
      </div>
    </div>

  </div>

  @push('scripts')
      <script>
          document.addEventListener('livewire:init', () => {
              const ctx = document.getElementById('vendorChart');
              if (!ctx) return;

              new Chart(ctx, {
                  type: 'bar',
                  data: {
                      labels: @json($weekLabels),
                      datasets: [{
                          label: 'Sales (৳)',
                          data: @json($weekSales),
                          backgroundColor: (() => {
                              const colors = @json($weekSales).map(() => 'rgba(233,30,140,.35)');
                              if (colors.length) colors[colors.length - 1] = '#e91e8c';
                              return colors;
                          })(),
                          borderRadius: 8,
                          borderSkipped: false
                      }]
                  },
                  options: {
                      responsive: true,
                      maintainAspectRatio: false,
                      plugins: {
                          legend: { display: false },
                          tooltip: { callbacks: { label: ctx => ' ৳' + ctx.raw.toLocaleString() } }
                      },
                      scales: {
                          x: { grid: { display: false } },
                          y: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { callback: v => '৳' + (v / 1000) + 'k' } }
                      }
                  }
              });
          });
      </script>
  @endpush