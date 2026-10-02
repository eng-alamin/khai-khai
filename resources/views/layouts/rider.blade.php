{{-- resources/views/layouts/rider.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <script>
    (function () {
      var t = localStorage.getItem('kk-theme') ||
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $title ?? config('app.name') }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
  <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}"/>
  <link rel="stylesheet" href="{{ asset('assets/css/blade.css') }}"/>
  <style>
    #toast-wrap { position:fixed; bottom:24px; right:24px; z-index:9999; display:flex; flex-direction:column; gap:8px; }
    .toast-item { background:var(--dark, #1f2937); color:#fff; padding:12px 20px; border-radius:12px; font-size:14px; font-weight:600; box-shadow:0 8px 32px rgba(0,0,0,0.2); animation:toastIn 0.3s ease; display:flex; align-items:center; gap:10px; }
    .toast-item.success { background:#065f46; }
    .toast-item.danger, .toast-item.error { background:#991b1b; }
    .toast-item.warning { background:#92400e; }
    .toast-item.info    { background:#1e40af; }
    @keyframes toastIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }
  </style>
  @stack('styles')
  @livewireStyles
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="mainSidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><span class="material-icons-round">two_wheeler</span></div>
    <div class="brand-text">
      <div class="brand-name"><span>Khai</span>Khai</div>
      <div class="brand-sub">Rider Panel</div>
    </div>
  </div>
  <div class="sidebar-scroll">
    <ul class="list-unstyled mb-0">
      <li class="nav-section">Delivery</li>
      <li class="nav1-item"><a class="nav1-link {{ request()->routeIs('rider.dashboard') ? 'active' : '' }}" href="{{ route('rider.dashboard') }}"><span class="material-icons-round nav-icon">dashboard</span><span class="nav-label">Dashboard</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ request()->routeIs('rider.delivery.ongoing') ? 'active' : '' }}" href="{{ route('rider.delivery.ongoing') }}"><span class="material-icons-round nav-icon">two_wheeler</span><span class="nav-label">Delivery Ongoing</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ request()->routeIs('rider.delivery.history') ? 'active' : '' }}" href="{{ route('rider.delivery.history') }}"><span class="material-icons-round nav-icon">history</span><span class="nav-label">Delivery History</span></a></li>
      <li class="nav-section">Report</li>
      <li class="nav1-item"><a class="nav1-link {{ request()->routeIs('rider.finance') ? 'active' : '' }}" href="{{ route('rider.finance') }}"><span class="material-icons-round nav-icon">payments</span><span class="nav-label">My Income</span></a></li>
      <li class="nav-section">My Account</li>
      <li class="nav1-item"><a class="nav1-link {{ request()->routeIs('rider.settings') ? 'active' : '' }}" href="{{ route('rider.settings') }}"><span class="material-icons-round nav-icon">person</span><span class="nav-label">Settings</span></a></li>
      <li class="nav1-item">
        <a class="nav1-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit()">
          <span class="material-icons-round nav-icon">logout</span><span class="nav-label">Logout</span>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
          @csrf
        </form>
      </li>
    </ul>
  </div>
  <div class="sidebar-footer">
    <div class="sf-user">
      <img src="{{ auth()->user()->avatar ?? 'https://i.pravatar.cc/80?img=12' }}" class="sf-avatar" alt="{{ auth()->user()->name ?? 'Rider' }}"/>
      <div>
        <div class="sf-name">{{ config('app.name') }}</div>
        <div class="sf-role">{{ ucfirst(auth()->user()->role ?? 'Unknown') }}</div>
      </div>
    </div>
  </div>
</aside>

<!-- MAIN WRAP -->
<div class="main-wrap">
  <nav class="topnav">
    <button class="topnav-toggle" onclick="toggleSidebar()"><span class="material-icons-round">menu</span></button>
    <div class="breadcrumb-wrap">
      <div class="breadcrumb-title">{{ $breadcrumbTitle ?? config('app.name') }}</div>
    </div>
    <div class="topnav-right d-flex align-items-center gap-1 ms-auto">
      <button class="icon-btn kk-theme-toggle" onclick="toggleTheme()" title="Theme পরিবর্তন করুন">
        <span class="material-icons-round" id="themeIcon">dark_mode</span>
      </button>
      <div class="dropdown">
        <a href="{{ route('rider.delivery.ongoing') }}" class="icon-btn">
          <span class="material-icons-round">two_wheeler</span>
        </a>
      </div>
      @livewire('notification-bell')
      <div class="dropdown">
        <img src="{{ auth()->user()->avatar ?? 'https://i.pravatar.cc/80?img=12' }}" class="topnav-avatar" data-bs-toggle="dropdown" data-bs-auto-close="outside" alt="{{ auth()->user()->name ?? 'Rider' }}"/>
        <div class="dropdown-menu dropdown-menu-end user-dropdown-menu">
          <div class="user-info-block">
            <img src="{{ auth()->user()->avatar ?? 'https://i.pravatar.cc/80?img=12' }}" class="user-avatar-lg" alt="{{ auth()->user()->name ?? 'Rider' }}"/>
            <div>
              <div class="user-name">{{ auth()->user()->name ?? 'Unknown' }} <span class="badge-pro">Rider</span></div>
              <a href="#" class="user-email">{{ auth()->user()->email ?? 'Unknown' }}</a>
            </div>
          </div>
          <hr class="dropdown-sep"/>

          <div class="ud-item">
            <a href="{{ route('rider.profile') }}" class="ud-link">
              <span class="d-flex align-items-center">
                <span class="material-icons-round ud-icon">person</span>
                Profile
              </span>
            </a>
          </div>

          <div class="ud-item">
            <a href="{{ route('logout') }}" class="ud-link signout" onclick="event.preventDefault(); document.getElementById('logout-form-topnav').submit()">
              <span class="d-flex align-items-center">
                <span class="material-icons-round ud-icon">logout</span>
                Logout
              </span>
            </a>
            <form id="logout-form-topnav" action="{{ route('logout') }}" method="POST" style="display:none;">
              @csrf
            </form>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <div class="page-body">

    {{ $slot }}

  </div>
  <!-- /page-body -->
</div>
<!-- /main-wrap -->

{{-- TOAST --}}
<div id="toast-wrap"></div>

<!-- MOBILE BOTTOM NAV -->
<nav class="mob-bottom-nav">
  <div class="mob-nav-items">
    <a href="{{ route('rider.dashboard') }}" class="mob-nav-item {{ request()->routeIs('rider.dashboard') ? 'active' : '' }}"><span class="material-icons-round">dashboard</span><span>Dashboard</span></a>
    <a href="{{ route('rider.delivery.ongoing') }}" class="mob-nav-item {{ request()->routeIs('rider.delivery.ongoing') ? 'active' : '' }}"><span class="material-icons-round">two_wheeler</span><span>Delivery</span></a>
    <a href="{{ route('rider.delivery.history') }}" class="mob-nav-item {{ request()->routeIs('rider.delivery.history') ? 'active' : '' }}"><span class="material-icons-round">history</span><span>History</span></a>
    <a href="{{ route('rider.finance') }}" class="mob-nav-item {{ request()->routeIs('rider.finance') ? 'active' : '' }}"><span class="material-icons-round">payments</span><span>Income</span></a>
    <a href="{{ route('rider.profile') }}" class="mob-nav-item {{ request()->routeIs('rider.profile') ? 'active' : '' }}"><span class="material-icons-round">person</span><span>Profile</span></a>
  </div>
</nav>

{{-- Persistent background location tracker — sends location on every page while rider is online --}}
@auth
  @if(auth()->user()->role === 'rider')
    @livewire('rider.location-tracker')
  @endif
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function toggleTheme() {
    var html = document.documentElement;
    var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('kk-theme', next);
    var icon = document.getElementById('themeIcon');
    if (icon) icon.textContent = next === 'dark' ? 'light_mode' : 'dark_mode';
  }
  document.addEventListener('DOMContentLoaded', function () {
    var icon = document.getElementById('themeIcon');
    if (icon) icon.textContent = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light_mode' : 'dark_mode';
  });

  function toggleSidebar() {
    document.getElementById('mainSidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
  }
  function closeSidebar() {
    document.getElementById('mainSidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
  }
  function toggleNav1(el) {
    el.classList.toggle('open');
    el.nextElementSibling.classList.toggle('open');
  }

  function showToast(msg, type = '') {
    const w = document.getElementById('toast-wrap');
    if (!w || !msg) return;
    const t = document.createElement('div');
    t.className = 'toast-item ' + (type || '');
    t.textContent = msg;
    w.appendChild(t);
    setTimeout(() => t.remove(), 3000);
  }

  // Show any session()->flash('success'/'error', ...) message set by
  // rider Livewire components.
  document.addEventListener('DOMContentLoaded', () => {
    @if (session('success'))
      showToast(@json(session('success')), 'success');
    @endif
    @if (session('error'))
      showToast(@json(session('error')), 'danger');
    @endif
  });

  // Show any $this->dispatch('show-toast', message: ..., type: ...)
  // events fired directly from a Livewire component (e.g. DeliveryOngoingComponent).
  document.addEventListener('livewire:initialized', () => {
    Livewire.on('show-toast', (payload) => {
      const data = Array.isArray(payload) ? payload[0] : payload;
      showToast(data?.message, data?.type);
    });

    Livewire.on('order-completed', () => {
      showToast('✅ Delivery completed!', 'success');
    });
  });
</script>


<style>
      /* ═══════════════════════════════════════════
        RIDER — Dashboard
        (add to blade.css; --dark/--muted/--border/--radius/--shadow-sm/
        --success/--warning/--danger already defined at :root in blade.css)
      ═══════════════════════════════════════════ */

      .stats-grid {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          gap: 14px;
      }
      @media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
      @media (max-width: 500px) { .stats-grid { grid-template-columns: 1fr; } }

      .stat-body { flex: 1; z-index: 1; }

      .best-badge {
          margin-top: 5px;
          font-size: 11px;
          font-weight: 700;
          color: var(--success);
          display: flex;
          align-items: center;
          gap: 4px;
      }

      .stat-bg-circle {
          position: absolute;
          right: -18px; bottom: -18px;
          width: 70px; height: 70px;
          border-radius: 50%;
          background: rgba(0,0,0,.04);
      }

      .main-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 14px;
      }
      @media (max-width: 768px) { .main-grid { grid-template-columns: 1fr; } }

      .count-badge {
          background: #fce7f3;
          color: #db2777;
          font-size: 12px;
          font-weight: 800;
          padding: 4px 11px;
          border-radius: 999px;
      }

      .ongoing-card {
          border: 1px solid var(--border);
          border-radius: 12px;
          padding: 14px 16px;
      }

      .order-num {
          font-size: 15px;
          font-weight: 900;
          color: #7c3aed;
      }

      .info-row {
          display: flex; align-items: flex-start; gap: 7px;
          color: var(--muted);
      }
      .info-icon { font-size: 14px; flex-shrink: 0; margin-top: 1px; }

      .btn-kk {
          display: inline-flex; align-items: center; justify-content: center;
          gap: 5px; padding: 9px 14px; border-radius: 10px;
          font-size: 13px; font-weight: 700; border: none;
          cursor: pointer; white-space: nowrap; text-decoration: none;
          transition: opacity .15s, transform .1s;
      }
      .btn-kk:hover  { opacity: .87; }
      .btn-kk:active { transform: scale(.97); }
      .btn-kk:disabled { opacity: .5; cursor: not-allowed; }

      .badge-kk {
          display: inline-flex; align-items: center;
          padding: 4px 11px; border-radius: 999px;
          font-size: 12px; font-weight: 700; white-space: nowrap;
      }

      .bar-row {
          display: flex;
          align-items: center;
          gap: 10px;
      }
      .bar-label {
          width: 30px;
          font-size: 13px;
          font-weight: 700;
          color: var(--muted);
          flex-shrink: 0;
          text-align: right;
      }
      .bar-track {
          flex: 1;
          height: 10px;
          background: #f0f0f5;
          border-radius: 999px;
          overflow: hidden;
      }
      .bar-fill {
          height: 100%;
          border-radius: 999px;
          background: linear-gradient(90deg, #f472b6, #ec4899);
          transition: width .4s ease;
          min-width: 4px;
      }
      .bar-amount {
          width: 54px;
          font-size: 13px;
          font-weight: 700;
          color: var(--dark);
          text-align: right;
          flex-shrink: 0;
      }

      .spinner {
          display: inline-block;
          width: 14px; height: 14px;
          border: 2px solid rgba(255,255,255,.4);
          border-top-color: #fff;
          border-radius: 50%;
          animation: spin .6s linear infinite;
      }
      @keyframes spin { to { transform: rotate(360deg); } }
      /* ═══════════════════════════════════════════
        RIDER — Delivery History
        (.btn-kk already added in blade-css-additions_1-dashboard.css — reused here)
      ═══════════════════════════════════════════ */

      .dh-card {
          padding: 22px 24px;
          width: 100%;
      }

      .dh-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          margin-bottom: 18px;
      }
      .dh-title {
          font-size: 19px;
          font-weight: 800;
          color: var(--pink);
      }
      .dh-badge-total {
          background: #fce7f3;
          color: var(--pink);
          font-size: 12px;
          font-weight: 700;
          padding: 5px 14px;
          border-radius: 999px;
          white-space: nowrap;
      }

      .dh-table-wrap { overflow-x: auto; }
      .dh-table {
          width: 100%;
          border-collapse: collapse;
          font-size: 14px;
      }
      .dh-table thead th {
          background: #f9fafb;
          color: var(--muted);
          font-weight: 700;
          font-size: 12px;
          text-transform: uppercase;
          letter-spacing: .4px;
          text-align: left;
          padding: 12px 14px;
          white-space: nowrap;
      }
      .dh-table tbody td {
          padding: 14px;
          border-bottom: 1px solid var(--border);
          white-space: nowrap;
      }
      .dh-table tbody tr:last-child td { border-bottom: none; }
      .dh-table tbody tr:hover { background: #fafafa; }

      .dh-order      { font-weight: 800; color: var(--dark); }
      .dh-customer   { color: var(--dark); }
      .dh-restaurant { color: #2563eb; font-weight: 600; }
      .dh-date       { color: var(--muted); font-size: 13px; }
      .dh-earning    { color: var(--success); font-weight: 800; }
      .dh-rating     { color: #f59e0b; font-weight: 700; }

      .dh-empty {
          text-align: center;
          padding: 30px 0;
          color: var(--muted);
      }

      .dh-pagination {
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 16px;
          margin-top: 18px;
      }
      .dh-page-info {
          font-size: 13px;
          color: var(--muted);
          font-weight: 700;
      }

      .btn-ghost-kk {
          background: #f3f4f6;
          color: var(--muted);
      }
      /* ═══════════════════════════════════════════
        RIDER — Ongoing / Available Deliveries
        (.btn-kk, .badge-kk, .info-row, .info-icon, .spinner already added
        in blade-css-additions_1-dashboard.css — reused here)
      ═══════════════════════════════════════════ */

      .call-btn {
          display: inline-flex; align-items: center; gap: 4px;
          padding: 3px 10px; border-radius: 999px;
          background: #eff6ff; color: #1d4ed8;
          font-size: 11px; font-weight: 700; text-decoration: none;
          white-space: nowrap; flex-shrink: 0;
          transition: background .15s;
      }
      .call-btn:hover { background: #dbeafe; }

        .items-box {
            background: #f9fafb; border: 1px solid var(--border);
            border-radius: 10px; padding: 10px 14px;
        }

        [x-cloak] { display: none !important; }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        @media (max-width: 600px) {
            .stats-row { grid-template-columns: 1fr; }
        }

        .payout-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
        }
        .payout-row:last-child { border-bottom: none; }
        .payout-left  { display: flex; flex-direction: column; }
        .payout-right { display: flex; align-items: center; gap: 10px; }
        /* ═══════════════════════════════════════════
          RIDER — Order Route Map (Leaflet marker pins)
        ═══════════════════════════════════════════ */

        .kk-map-pin {
            display: flex; align-items: center; justify-content: center;
            width: 34px; height: 34px; border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            box-shadow: 0 2px 6px rgba(0,0,0,.35);
            border: 2px solid #fff;
        }
        .kk-map-pin span {
            transform: rotate(45deg);
            font-size: 16px; line-height: 1;
        }
        .kk-pin-restaurant { background: #f97316; }
        .kk-pin-destination { background: #2563eb; }
        .kk-pin-rider {
            background: transparent; box-shadow: none; border: none;
            transform: none; display: flex; align-items: center; justify-content: center;
        }
        .kk-pin-rider span { transform: none; font-size: 24px; }
        /* ═══════════════════════════════════════════
          RIDER — Profile page (prof-* namespaced classes)
          Already uses blade.css vars correctly — no renaming needed.
          NOTE: .btn-cancel, .btn-confirm-delete, .spinner-sm here are
          generic/shared (same pattern as the admin product-component
          delete modal) — add ONCE globally, don't duplicate per-module.
        ═══════════════════════════════════════════ */


              .main-content {
                  background: var(--bg);
                  min-height: 100vh;
                  padding: 0 0 80px;
                  font-family: var(--font);
              }

              /* ── Top Bar ── */
              .prof-topbar {
                  display: flex; align-items: center; justify-content: space-between;
                  padding: 20px 16px 12px;
                  background: var(--bg);
                  position: sticky; top: 0; z-index: 50;
              }
              .prof-topbar-title {
                  display: flex; align-items: center; gap: 8px;
                  font-size: 1.18rem; font-weight: 700; color: var(--dark);
              }

              /* ── Summary Card ── */
              .prof-summary-card {
                  margin: 0 16px 12px;
                  background: var(--card-bg);
                  border: 1.5px solid var(--border);
                  border-radius: var(--radius-lg);
                  padding: 18px;
                  display: flex; align-items: center; gap: 16px;
                  box-shadow: var(--shadow-card);
              }
              .prof-avatar-wrap { position: relative; flex-shrink: 0; }
              .prof-avatar {
                  width: 72px; height: 72px; border-radius: 50%;
                  background: rgba(255,61,139,.12); color: var(--pink);
                  display: flex; align-items: center; justify-content: center;
                  font-size: 1.3rem; font-weight: 700;
                  overflow: hidden; border: 2px solid var(--pink-light);
              }
              .prof-avatar img { width: 100%; height: 100%; object-fit: cover; }
              .prof-avatar-edit {
                  position: absolute; bottom: -2px; right: -2px;
                  width: 28px; height: 28px; border-radius: 50%;
                  background: var(--pink); color: #fff;
                  display: flex; align-items: center; justify-content: center;
                  cursor: pointer; border: 2px solid var(--card-bg);
                  transition: var(--transition);
              }
              .prof-avatar-edit:hover { background: #e02d7a; }
              .prof-avatar-edit .material-icons-round { font-size: .85rem; }

              .prof-summary-info { flex: 1; min-width: 0; }
              .prof-summary-name { font-size: 1.05rem; font-weight: 700; color: var(--dark); }
              .prof-summary-role { display: flex; align-items: center; gap: 6px; margin-top: 4px; }
              .prof-role-badge {
                  display: inline-block; padding: 2px 10px; border-radius: 50px;
                  font-size: .68rem; font-weight: 600;
                  background: var(--pink-light); color: var(--pink);
              }
              .prof-verified-badge {
                  display: inline-flex; align-items: center; gap: 2px;
                  font-size: .68rem; font-weight: 600; color: #1A9453;
              }
              .prof-verified-badge .material-icons-round { font-size: .82rem; }
              .prof-summary-meta {
                  display: flex; flex-direction: column; gap: 1px;
                  margin-top: 6px; font-size: .76rem; color: var(--muted);
              }

              /* ── Avatar Actions Row ── */
              .prof-avatar-actions {
                  margin: 0 16px 12px; padding: 10px 14px;
                  background: var(--bg); border: 1px solid var(--border);
                  border-radius: var(--radius-sm);
                  display: flex; align-items: center; justify-content: space-between;
                  gap: 10px; flex-wrap: wrap;
              }
              .prof-avatar-actions-text { font-size: .76rem; color: var(--muted); }
              .prof-avatar-remove-link, .prof-avatar-cancel {
                  display: inline-flex; align-items: center; gap: 4px;
                  background: #FFF0F0; border: none; border-radius: var(--radius-sm);
                  padding: 5px 12px; font-size: .74rem; color: #E53935;
                  cursor: pointer; font-family: var(--font); font-weight: 600;
                  transition: var(--transition); flex-shrink: 0;
              }
              .prof-avatar-remove-link:hover, .prof-avatar-cancel:hover { background: #FFD6D6; }
              .prof-avatar-remove-link .material-icons-round,
              .prof-avatar-cancel .material-icons-round { font-size: .8rem; }

              /* ── Section Cards ── */
              .prof-card {
                  margin: 0 16px 16px;
                  background: var(--card-bg);
                  border: 1.5px solid var(--border);
                  border-radius: var(--radius-lg);
                  box-shadow: var(--shadow-card);
                  overflow: hidden;
              }
              .prof-card-header {
                  display: flex; align-items: center; gap: 8px;
                  padding: 14px 18px;
                  font-size: .88rem; font-weight: 700; color: var(--dark);
                  border-bottom: 1px solid var(--border);
              }
              .prof-card-header .material-icons-round { font-size: 1.05rem; color: var(--pink); }
              .prof-card-body { padding: 18px; }

              /* ── Form ── */
              .prof-form-group { margin-bottom: 14px; }
              .prof-form-label {
                  display: block; font-size: .8rem; font-weight: 600;
                  color: var(--soft-dark); margin-bottom: 5px;
              }
              .prof-form-label .req { color: var(--pink); margin-left: 2px; }
              .prof-form-control {
                  width: 100%; padding: 10px 12px;
                  border: 1.5px solid var(--border); border-radius: var(--radius-sm);
                  font-family: var(--font); font-size: .84rem; color: var(--dark);
                  background: var(--bg); outline: none; transition: var(--transition);
                  box-sizing: border-box;
              }
              .prof-form-control:focus {
                  border-color: var(--pink); box-shadow: 0 0 0 3px rgba(255,61,139,.1); background: #fff;
              }
              .prof-form-control.is-invalid { border-color: #E53935; }
              .prof-invalid-feedback { color: #E53935; font-size: .74rem; margin-top: 4px; }

              .prof-row { display: flex; gap: 12px; }
              .prof-col { flex: 1; min-width: 0; }

              /* ── Upload Progress ── */
              .prof-upload-progress { margin: -4px 0 14px; }
              .prof-upload-bar {
                  height: 4px; border-radius: 99px;
                  background: linear-gradient(90deg, var(--pink), #ff8fab);
                  background-size: 200% 100%;
                  animation: shimmer 1.2s infinite; margin-bottom: 4px;
              }
              @keyframes shimmer {
                  0%   { background-position: 200% 0; }
                  100% { background-position: -200% 0; }
              }
              .prof-upload-progress small { font-size: .72rem; color: var(--muted); }

              /* ── Detail Grid ── */
              .prof-detail-grid {
                  display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
              }
              .prof-detail-item {
                  background: var(--bg); border-radius: var(--radius-sm);
                  padding: 10px 12px; border: 1px solid var(--border);
              }
              .prof-detail-label {
                  display: block; font-size: .7rem; font-weight: 600;
                  color: var(--muted); text-transform: uppercase;
                  letter-spacing: .04em; margin-bottom: 3px;
              }
              .prof-detail-value { font-size: .84rem; font-weight: 600; color: var(--dark); }

              /* ── Buttons ── */
              .btn-prof-primary {
                  padding: 11px 24px; background: var(--pink); color: #fff;
                  border: none; border-radius: var(--radius-md); font-family: var(--font);
                  font-size: .86rem; font-weight: 700; cursor: pointer; transition: var(--transition);
                  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
              }
              .btn-prof-primary:hover { background: #e02d7a; }
              .btn-prof-primary:disabled { opacity: .6; cursor: not-allowed; }

              /* ── Alerts ── */
              .prof-alert {
                  display: flex; align-items: center; gap: 8px;
                  margin: 12px 16px; padding: 12px 14px;
                  border-radius: var(--radius-md); font-size: .82rem;
              }
              .prof-alert span:not(.material-icons-round) { flex: 1; }
              .prof-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
              .prof-alert-error   { background: #FFF0F0; color: #E53935; border: 1px solid #FFBCBC; }
              .prof-alert-close {
                  background: none; border: none; cursor: pointer;
                  font-size: 1.1rem; color: inherit; padding: 0; line-height: 1;
              }

              /* ── Modal (remove avatar confirm) ── */
              .prof-modal-backdrop {
                  position: fixed; inset: 0;
                  background: rgba(10,10,30,.55); z-index: 1000;
                  display: flex; align-items: center; justify-content: center;
                  animation: fadeIn .18s ease; padding: 20px;
              }
              @keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }

              .prof-delete-modal {
                  background: var(--card-bg); border-radius: var(--radius-lg);
                  max-width: 320px; width: calc(100% - 32px);
                  padding: 28px 20px 20px; text-align: center;
                  animation: scaleIn .18s cubic-bezier(.4,0,.2,1);
              }
              @keyframes scaleIn {
                  from { transform: scale(.9); opacity: 0; }
                  to   { transform: scale(1);  opacity: 1; }
              }
              .prof-delete-icon {
                  width: 56px; height: 56px; border-radius: 50%;
                  background: #FFF0F0; display: flex; align-items: center;
                  justify-content: center; margin: 0 auto 14px; font-size: 1.6rem;
              }
              .prof-delete-modal h6 { font-size: .98rem; font-weight: 700; color: var(--dark); margin: 0 0 6px; }
              .prof-delete-modal p  { font-size: .8rem; color: var(--muted); margin: 0 0 20px; line-height: 1.5; }
              .prof-delete-actions  { display: flex; gap: 10px; justify-content: center; }

              .btn-cancel {
                  padding: 9px 20px; background: var(--bg); border: 1.5px solid var(--border);
                  border-radius: var(--radius-sm); font-family: var(--font); font-size: .82rem;
                  font-weight: 600; color: var(--soft-dark); cursor: pointer; transition: var(--transition);
              }
              .btn-cancel:hover { background: var(--border); }

              .btn-confirm-delete {
                  padding: 9px 20px; background: #E53935; border: none;
                  border-radius: var(--radius-sm); font-family: var(--font); font-size: .82rem;
                  font-weight: 600; color: #fff; cursor: pointer; transition: var(--transition);
                  display: flex; align-items: center; gap: 5px;
              }
              .btn-confirm-delete:hover { background: #c62828; }

              /* ── Spinner ── */
              .spinner-sm {
                  width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.4);
                  border-top-color: #fff; border-radius: 50%;
                  animation: spin .6s linear infinite;
              }
              /* @keyframes spin already defined above — removed duplicate */

              @media (max-width: 480px) {
                  .prof-row { flex-direction: column; }
                  .prof-detail-grid { grid-template-columns: 1fr; }
                  .prof-summary-card { flex-direction: column; text-align: center; }
              }

        .rider-setting .card {
          padding: 0;
          overflow: hidden;
        }

        /* ── Setting hero header ── */
        .setting-hero {
          position: relative;
          text-align: center;
          padding: 30px 20px 22px;
          overflow: hidden;
        }
        .setting-hero-bg {
          position: absolute;
          inset: 0;
          background: linear-gradient(160deg, var(--pink-soft) 0%, #fff 65%);
          z-index: 0;
        }
        .setting-hero > * { position: relative; z-index: 1; }


        .avatar-img,
        .avatar-fallback {
          width: 92px;
          height: 92px;
          border-radius: 50%;
          object-fit: cover;
          display: flex;
          align-items: center;
          justify-content: center;
          box-shadow: var(--shadow-hover);
          border: 3px solid #fff;
        }
        .avatar-fallback {
          background: linear-gradient(135deg, var(--pink), var(--accent));
          color: #fff;
          font-size: 34px;
          font-weight: 800;
        }
        .avatar-status-dot {
          position: absolute;
          right: 3px;
          bottom: 3px;
          width: 16px;
          height: 16px;
          border-radius: 50%;
          border: 3px solid #fff;
        }
        .avatar-status-dot.is-online  { background: var(--success); }
        .avatar-status-dot.is-offline { background: #9ca3af; }

        .setting-name {
          font-size: 21px;
          font-weight: 800;
          color: var(--dark);
          letter-spacing: -.2px;
        }
        .setting-role {
          color: var(--muted);
          font-size: 13px;
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: .5px;
          margin-top: 2px;
        }

        .online-toggle-btn {
          padding: 10px 24px !important;
          border-radius: 999px !important;
          font-weight: 700 !important;
          box-shadow: 0 6px 16px rgba(233,30,140,.25);
          transition: transform .15s ease, box-shadow .15s ease;
        }
        .online-toggle-btn:hover { transform: translateY(-1px); }

        /* ── Form ── */
        .setting-form {
          border-top: 1px solid var(--border);
          padding: 22px 22px 24px;
        }

        .form-section-label {
          font-size: 11px;
          font-weight: 800;
          text-transform: uppercase;
          letter-spacing: .7px;
          color: var(--pink);
          margin: 18px 0 10px;
        }
        .form-section-label:first-child { margin-top: 0; }

        .form-label-kk {
          display: block;
          font-size: 12px;
          font-weight: 700;
          color: var(--muted);
          margin-bottom: 6px;
          text-transform: uppercase;
          letter-spacing: .4px;
        }

        .form-control-kk {
          width: 100%;
          padding: 11px 14px;
          border: 1.5px solid var(--border);
          border-radius: 10px;
          font-size: 14px;
          color: var(--dark);
          background: #fafafa;
          transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        }
        .form-control-kk:focus {
          outline: none;
          border-color: var(--pink);
          background: #fff;
          box-shadow: 0 0 0 3px rgba(233,30,140,.1);
        }

        .input-icon-wrap { position: relative; }
        .input-icon {
          position: absolute;
          left: 14px;
          top: 50%;
          transform: translateY(-50%);
          color: var(--muted);
          font-size: 13px;
          pointer-events: none;
        }
        .form-control-kk.has-icon { padding-left: 38px; }

        .form-group { margin-bottom: 16px; }

        /* ── Buttons ── */

        .btn-primary-kk {
          background: linear-gradient(135deg, var(--pink), var(--pink-light));
          color: #fff;
        }
        .btn-sm-kk { padding: 6px 12px; font-size: 12px; }

        .save-btn {
          width: 100%;
          justify-content: center;
          padding: 13px !important;
          font-size: 14px !important;
          border-radius: 12px !important;
          margin-top: 6px;
          box-shadow: 0 8px 20px rgba(233,30,140,.22);
        }

        /* ── Badges ── */
        .badge-green { background: #d1fae5; color: #065f46; }

        /* ── Map ── */
        .map-frame {
          border-radius: var(--radius);
          border: 1px solid var(--border);
          overflow: hidden;
          box-shadow: var(--shadow-sm);
        }
        #riderLocationMap {
          height: 220px;
          width: 100%;
        }
        .location-hint {
          font-size: 12px;
          color: var(--muted);
          margin-top: 8px;
          display: flex;
          align-items: center;
          gap: 6px;
        }
        .location-hint i { color: var(--pink); }

        /* ── Stat cards ── */
        .setting-stat-card {
          background: #fff;
          border: 1px solid var(--border);
          border-radius: var(--radius);
          box-shadow: var(--shadow-sm);
          padding: 20px;
          display: flex;
          align-items: center;
          gap: 16px;
          position: relative;
          overflow: hidden;
          transition: transform .15s ease, box-shadow .15s ease;
        }
        .setting-stat-card:hover {
          transform: translateY(-2px);
          box-shadow: 0 10px 24px rgba(0,0,0,.08);
        }
        .setting-stat-card::after {
          content: "";
          position: absolute;
          right: -14px;
          bottom: -14px;
          width: 80px;
          height: 80px;
          border-radius: 50%;
          background: currentColor;
          opacity: .06;
        }
        .setting-stat-icon {
          width: 52px;
          height: 52px;
          border-radius: 14px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 20px;
          flex-shrink: 0;
        }
        .setting-stat-info .num {
          font-size: 25px;
          font-weight: 800;
          line-height: 1;
        }
        .setting-stat-info .label {
          font-size: 12px;
          color: var(--muted);
          margin-top: 6px;
          font-weight: 600;
        }

        .sc-pink   .setting-stat-icon { background: var(--pink-soft); color: var(--pink); }
        .sc-pink   .num        { color: var(--pink); }
        .sc-green  .setting-stat-icon { background: #d1fae5; color: var(--success); }
        .sc-green  .num        { color: var(--success); }
        .sc-orange .setting-stat-icon { background: #fef3c7; color: #ca8a04; }
        .sc-orange .num        { color: #ca8a04; }
</style>

@stack('scripts')
@livewireScripts
</body>
</html>