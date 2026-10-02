<!DOCTYPE html>
<html lang="bn">
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
  <link rel="stylesheet" href="{{asset('assets/css/theme.css')}}"/>
  <link rel="stylesheet" href="{{asset('assets/css/blade.css')}}"/>
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
    <div class="brand-icon"><span class="material-icons-round">store</span></div>
    <div class="brand-text">
      <div class="brand-name"><span>Khai</span>Khai</div>
      <div class="brand-sub">Vendor Panel</div>
    </div>
  </div>
  <div class="sidebar-scroll">
    <ul class="list-unstyled mb-0">
      <li class="nav-section">Dashboard</li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'dashboard') == true ? 'active' : '' }}" href="{{ route('vendor.dashboard') }}"><span class="material-icons-round nav-icon">dashboard</span><span class="nav-label">Dashboard</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'orders/live') == true ? 'active' : '' }}" href="{{ route('vendor.orders.live') }}"><span class="material-icons-round nav-icon">shopping_bag</span><span class="nav-label">Live Orders</span>@livewire('vendor.live-order-count')</a></li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'orders/all') == true ? 'active' : '' }}" href="{{ route('vendor.orders.list') }}"><span class="material-icons-round nav-icon">list_alt</span><span class="nav-label">All Orders</span></a></li>
      <li class="nav-section">Restaurant</li>
      
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'menu/items') == true ? 'active' : '' }}" href="{{ route('vendor.menu.items') }}"><span class="material-icons-round nav-icon">restaurant_menu</span><span class="nav-label">Items</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'promotions') == true ? 'active' : '' }}" href="{{ route('vendor.promotions') }}"><span class="material-icons-round nav-icon">local_offer</span><span class="nav-label">Promotions</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'coupons') == true ? 'active' : '' }}" href="{{ route('vendor.coupons') }}"><span class="material-icons-round nav-icon">local_offer</span><span class="nav-label">Coupons</span></a></li>
      <li class="nav-section">Report</li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'finances') == true ? 'active' : '' }}" href="{{ route('vendor.finances') }}"><span class="material-icons-round nav-icon">payments</span><span class="nav-label">Finances</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'reviews') == true ? 'active' : '' }}" href="{{ route('vendor.reviews') }}"><span class="material-icons-round nav-icon">star_rate</span><span class="nav-label">Reviews</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'settings') == true ? 'active' : '' }}" href="{{ route('vendor.settings') }}"><span class="material-icons-round nav-icon">manage_accounts</span><span class="nav-label">Settings</span></a></li>
    </ul>
  </div>
  <div class="sidebar-footer">
    <div class="sf-user">
      <img src="{{ auth()->user()->avatar ?? 'https://i.pravatar.cc/80?img=12' }}" class="sf-avatar" alt="{{ auth()->user()->name ?? 'Vendor' }}"/>
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
      {{-- <div class="breadcrumb-sub">মা'র রান্নাঘর — গাজীপুর বাজার</div> --}}
    </div>
    <div class="topnav-right d-flex align-items-center gap-1 ms-auto">
      <button class="icon-btn kk-theme-toggle" onclick="toggleTheme()" title="Theme পরিবর্তন করুন">
        <span class="material-icons-round" id="themeIcon">dark_mode</span>
      </button>
      @livewire('notification-bell')
      <div class="dropdown">
        <img src="{{ auth()->user()->avatar ?? 'https://i.pravatar.cc/80?img=12' }}" class="topnav-avatar" data-bs-toggle="dropdown" data-bs-auto-close="outside" alt="{{ auth()->user()->name ?? 'Vendor' }}"/>
        <div class="dropdown-menu dropdown-menu-end user-dropdown-menu">
          <div class="user-info-block">
            <img src="{{ auth()->user()->avatar ?? 'https://i.pravatar.cc/80?img=12' }}" class="user-avatar-lg" alt="{{ auth()->user()->name ?? 'Vendor' }}"/>
            <div>
              <div class="user-name">{{ auth()->user()->name ?? 'Unknown' }} <span class="badge-pro">Vendor</span></div>
              <a href="#" class="user-email">{{ auth()->user()->email ?? 'Unknown' }}</a>
            </div>
          </div>
          <hr class="dropdown-sep"/>

          <div class="ud-item">
            <a href="{{ route('vendor.profile') }}" class="ud-link">
              <span class="d-flex align-items-center">
                <span class="material-icons-round ud-icon">person</span>
                Profile
              </span>
            </a>
          </div>

          <div class="ud-item">
            <a href="{{route('logout') }}" class="ud-link signout" onclick="event.preventDefault(); document.getElementById('logout-form').submit()">
              <span class="d-flex align-items-center">
                <span class="material-icons-round ud-icon">logout</span>
                  Logout
                </span>
              </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
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
    <a href="{{ route('vendor.dashboard') }}" class="mob-nav-item active"><span class="material-icons-round">dashboard</span><span>Dashboard</span></a>
    <a href="{{ route('vendor.orders.live') }}" class="mob-nav-item"><span class="material-icons-round">shopping_bag</span><span>Orders</span></a>
    <a href="{{ route('vendor.menu.items') }}" class="mob-nav-item"><span class="material-icons-round">restaurant_menu</span><span>Food</span></a>
    <a href="{{ route('vendor.finances') }}" class="mob-nav-item"><span class="material-icons-round">payments</span><span>Finances</span></a>
    <a href="#" class="mob-nav-item"><span class="material-icons-round">person</span><span>Profile</span></a>
  </div>
</nav>

<!-- ADD MENU MODAL -->
<div class="modal fade" id="addMenuModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">নতুন মেনু আইটেম যোগ করুন</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <div style="border:2px dashed var(--pink-mid);border-radius:10px;text-align:center;padding:20px;background:var(--pink-soft);cursor:pointer;">
              <span class="material-icons-round" style="font-size:28px;color:var(--pink)">cloud_upload</span>
              <div style="font-size:.8rem;font-weight:600;color:var(--pink);margin-top:6px">ছবি আপলোড করুন</div>
              <div style="font-size:.7rem;color:var(--muted)">JPG, PNG — সর্বোচ্চ 5MB</div>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label">আইটেমের নাম</label>
            <input type="text" class="form-control" placeholder="যেমন: মুরগির বিরিয়ানি"/>
          </div>
          <div class="col-6">
            <label class="form-label">মূল্য (৳)</label>
            <input type="number" class="form-control" placeholder="150"/>
          </div>
          <div class="col-6">
            <label class="form-label">ক্যাটাগরি</label>
            <select class="form-select">
              <option>ভাত</option><option>বিরিয়ানি</option><option>মাছ</option><option>ডেজার্ট</option><option>ড্রিংকস</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">বিবরণ</label>
            <textarea class="form-control" rows="2" placeholder="খাবারের বিস্তারিত বিবরণ..."></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-ghost-sm" data-bs-dismiss="modal">বাতিল</button>
        <button class="btn-pink" data-bs-dismiss="modal"><span class="material-icons-round">add</span> যোগ করুন</button>
      </div>
    </div>
  </div>
</div>

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
      // vendor Livewire components (most components use this pattern).
      document.addEventListener('DOMContentLoaded', () => {
        @if (session('success'))
          showToast(@json(session('success')), 'success');
        @endif
        @if (session('error'))
          showToast(@json(session('error')), 'danger');
        @endif
      });

      // Show any $this->dispatch('show-toast', message: ..., type: ...)
      // events fired directly from a Livewire component (e.g. CouponComponent).
      document.addEventListener('livewire:initialized', () => {
        Livewire.on('show-toast', (payload) => {
          const data = Array.isArray(payload) ? payload[0] : payload;
          showToast(data?.message, data?.type);
        });
      });
  </script>

@stack('scripts')
@livewireScripts
</body>
</html>
