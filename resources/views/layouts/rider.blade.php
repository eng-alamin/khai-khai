{{-- resources/views/layouts/rider.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
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
      <img
        src="{{ auth()->user()->avatar ?? asset('assets/img/default-rider.png') }}"
        class="sf-avatar"
        alt="{{ auth()->user()->name ?? 'Rider' }}"
      />
      <div>
        <div class="sf-name">{{ auth()->user()->name ?? 'Rider' }}</div>
        <div class="sf-role">Rider</div>
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
      <div class="dropdown">
        <a href="{{ route('rider.delivery.ongoing') }}" class="icon-btn">
          <span class="material-icons-round">two_wheeler</span>
        </a>
      </div>
      <div class="dropdown">
        <button class="icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside">
          <span class="material-icons-round">notifications</span>
          <span class="notif-badge">2</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end notif-dropdown-menu">
          <li><div class="notif-header"><h6>Notifications</h6></div></li>
          <li><a class="notif-item" href="{{ route('rider.delivery.ongoing') }}"><div class="notif-icon cart"><span class="material-icons-round">shopping_bag</span></div><div class="notif-text"><strong>New order assigned #KK2615</strong><span>Just now</span></div></a></li>
          <li><a class="notif-item" href="{{ route('rider.finance') }}"><div class="notif-icon podcast"><span class="material-icons-round">payments</span></div><div class="notif-text"><strong>Payout of Tk 1,250 completed</strong><span>1 hour ago</span></div></a></li>
          <li><div class="notif-footer"><a href="#">View all</a></div></li>
        </ul>
      </div>
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

@stack('scripts')
@livewireScripts
</body>
</html>