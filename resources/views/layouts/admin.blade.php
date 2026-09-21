<!DOCTYPE html>
<html lang="bn">
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
  <link rel="stylesheet" href="{{asset('assets/css/theme.css')}}"/>
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
      <div class="brand-sub">Admin Panel</div>
    </div>
  </div>
  <div class="sidebar-scroll">
    <ul class="list-unstyled mb-0">
      <li class="nav-section">Overview</li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'admin/dashboard') == true ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span class="material-icons-round nav-icon">dashboard</span><span class="nav-label">Dashboard</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'admin/orders') == true ? 'active' : '' }}" href="{{ route('admin.orders') }}"><span class="material-icons-round nav-icon">shopping_bag</span><span class="nav-label">Orders</span></a></li>
     
      <li class="nav-section">Management</li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'admin/products') == true ? 'active' : '' }}" href="{{ route('admin.products') }}"><span class="material-icons-round nav-icon">store</span><span class="nav-label">Products</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'admin/categories') == true ? 'active' : '' }}" href="{{ route('admin.categories') }}"><span class="material-icons-round nav-icon">category</span><span class="nav-label">Categories</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'admin/sliders') == true ? 'active' : '' }}" href="{{ route('admin.sliders') }}"><span class="material-icons-round nav-icon">category</span><span class="nav-label">Sliders</span></a></li>

      <li class="nav-section">Management</li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'admin/vendors') == true ? 'active' : '' }}" href="{{ route('admin.vendors') }}"><span class="material-icons-round nav-icon">storefront</span><span class="nav-label">Vendors</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'admin/riders') == true ? 'active' : '' }}" href="{{ route('admin.riders') }}"><span class="material-icons-round nav-icon">directions_bike</span><span class="nav-label">Riders</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'admin/customers') == true ? 'active' : '' }}" href="{{ route('admin.customers') }}"><span class="material-icons-round nav-icon">accessible</span><span class="nav-label">Customers</span></a></li>
      
      <li class="nav-section">Report</li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'admin/revenues') == true ? 'active' : '' }}" href="{{ route('admin.revenues') }}"><span class="material-icons-round nav-icon">attach_money</span><span class="nav-label">Revenues</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'admin/settings') == true ? 'active' : '' }}" href="{{ route('admin.settings') }}"><span class="material-icons-round nav-icon">manage_accounts</span><span class="nav-label">Settings</span></a></li>
    </ul>
  </div>
  <div class="sidebar-footer">
    <div class="sf-user">
      <img src="{{ auth()->user()->avatar ?? 'https://i.pravatar.cc/80?img=12' }}" class="sf-avatar" alt="{{ auth()->user()->name ?? 'Admin' }}"/>
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
      @php
        $__headerNotifications = \App\Models\Notification::query()
            ->where(function ($q) {
                $q->whereNull('user_id')->orWhere('user_id', auth()->id());
            })
            ->latest()
            ->limit(3)
            ->get();
        $__headerUnreadCount = \App\Models\Notification::query()
            ->where(function ($q) {
                $q->whereNull('user_id')->orWhere('user_id', auth()->id());
            })
            ->unread()
            ->count();
      @endphp
      <div class="dropdown">
        <button class="icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside">
          <span class="material-icons-round">notifications</span>
          @if($__headerUnreadCount > 0)
            <span class="notif-badge">{{ $__headerUnreadCount > 9 ? '9+' : $__headerUnreadCount }}</span>
          @endif
        </button>
        <ul class="dropdown-menu dropdown-menu-end notif-dropdown-menu">
          <li><div class="notif-header"><h6>Notifications</h6></div></li>
          @forelse($__headerNotifications as $__n)
            <li>
              <a class="notif-item" href="{{ route('admin.notifications') }}">
                <div class="notif-icon {{ $__n->priority === 'high' ? 'cart' : 'podcast' }}">
                  <span class="material-icons-round">{{ $__n->priority === 'high' ? 'priority_high' : 'notifications' }}</span>
                </div>
                <div class="notif-text">
                  <strong>{{ $__n->title }}</strong>
                  <span>{{ $__n->created_at->diffForHumans() }}</span>
                </div>
              </a>
            </li>
          @empty
            <li><div class="notif-item text-muted">No notifications yet</div></li>
          @endforelse
          <li><div class="notif-footer"><a href="{{ route('admin.notifications') }}">View all</a></div></li>
        </ul>
      </div>
      <div class="dropdown">
        <img src="{{ auth()->user()->avatar ?? 'https://i.pravatar.cc/80?img=12' }}" class="topnav-avatar" data-bs-toggle="dropdown" data-bs-auto-close="outside" alt="{{ auth()->user()->name ?? 'Admin' }}"/>
        <div class="dropdown-menu dropdown-menu-end user-dropdown-menu">
          <div class="user-info-block">
            <img src="{{ auth()->user()->avatar ?? 'https://i.pravatar.cc/80?img=12' }}" class="user-avatar-lg" alt="{{ auth()->user()->name ?? 'Admin' }}"/>
            <div>
              <div class="user-name">{{ auth()->user()->name ?? 'Unknown' }} <span class="badge-pro">{{ ucfirst(auth()->user()->role ?? 'Admin') }}</span></div>
              <a href="#" class="user-email">{{ auth()->user()->email ?? 'Unknown' }}</a>
            </div>
          </div>
          <hr class="dropdown-sep"/>

          <div class="ud-item">
            <a href="{{ route('admin.profile') }}" class="ud-link">
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

<!-- MOBILE BOTTOM NAV -->
<nav class="mob-bottom-nav">
  <div class="mob-nav-items">
    <a href="{{ route('admin.dashboard') }}" class="mob-nav-item active"><span class="material-icons-round">dashboard</span><span>Dashboard</span></a>
    <a href="{{ route('admin.orders') }}" class="mob-nav-item"><span class="material-icons-round">shopping_bag</span><span>Orders</span></a>
    <a href="{{ route('admin.revenues') }}" class="mob-nav-item"><span class="material-icons-round">attach_money</span><span>Revenues</span></a>
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
  </script>

@stack('scripts')
@livewireScripts
</body>
</html>