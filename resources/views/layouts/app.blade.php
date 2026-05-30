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
      <div class="brand-sub">Vendor Panel</div>
    </div>
  </div>
  <div class="sidebar-scroll">
    <ul class="list-unstyled mb-0">
      <li class="nav-section">Dashboard</li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'dashboard') == true ? 'active' : '' }}" href="{{ route('vendor.dashboard') }}"><span class="material-icons-round nav-icon">dashboard</span><span class="nav-label">Dashboard</span></a></li>
      <li class="nav1-item"><a class="nav1-link" href="vendor-orders.html"><span class="material-icons-round nav-icon">shopping_bag</span><span class="nav-label">লাইভ অর্ডার</span><span class="nav-badge">7</span></a></li>
      <li class="nav-section">রেস্তোরাঁ</li>
      
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'menu/items') == true ? 'active' : '' }}" href="{{ route('menu.items') }}"><span class="material-icons-round nav-icon">restaurant_menu</span><span class="nav-label">Items</span></a></li>
      <li class="nav1-item"><a class="nav1-link {{ str_contains(request()->url(), 'menu/categories') == true ? 'active' : '' }}" href="{{ route('menu.categories') }}"><span class="material-icons-round nav-icon">category</span><span class="nav-label">Categories</span></a></li>
      <li class="nav1-item"><a class="nav1-link" href="vendor-promo.html"><span class="material-icons-round nav-icon">local_offer</span><span class="nav-label">প্রমোশন</span></a></li>
      <li class="nav-section">রিপোর্ট</li>
      <li class="nav1-item"><a class="nav1-link" href="vendor-earnings.html"><span class="material-icons-round nav-icon">payments</span><span class="nav-label">আয়-ব্যয়</span></a></li>
      <li class="nav1-item"><a class="nav1-link" href="vendor-reviews.html"><span class="material-icons-round nav-icon">star_rate</span><span class="nav-label">রিভিউ</span></a></li>
      <li class="nav1-item">
        <div class="nav1-link" onclick="toggleNav1(this)">
          <span class="material-icons-round nav-icon">manage_accounts</span>
          <span class="nav-label">সেটিংস</span>
          <span class="material-icons-round nav-arrow">expand_more</span>
        </div>
        <div class="nav2-collapse">
          <ul class="list-unstyled">
            <li class="nav2-item"><div class="nav2-link"><span class="nav2-icon">R</span><span class="nav2-label">রেস্তোরাঁ তথ্য</span></div></li>
            <li class="nav2-item"><div class="nav2-link"><span class="nav2-icon">H</span><span class="nav2-label">খোলার সময়</span></div></li>
            <li class="nav2-item"><div class="nav2-link"><span class="nav2-icon">P</span><span class="nav2-label">পেমেন্ট</span></div></li>
          </ul>
        </div>
      </li>
    </ul>
  </div>
  <div class="sidebar-footer">
    <div class="sf-user">
      <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=80&q=70" class="sf-avatar" alt="Vendor"/>
      <div><div class="sf-name">মা'র রান্নাঘর</div><div class="sf-role">Restaurant Owner</div></div>
    </div>
  </div>
</aside>

<!-- MAIN WRAP -->
<div class="main-wrap">
  <nav class="topnav">
    <button class="topnav-toggle" onclick="toggleSidebar()"><span class="material-icons-round">menu</span></button>
    <div class="breadcrumb-wrap">
      <div class="breadcrumb-title">Vendor Dashboard</div>
      <div class="breadcrumb-sub">মা'র রান্নাঘর — গাজীপুর বাজার</div>
    </div>
    <div class="topnav-search d-none d-md-flex">
      <span class="material-icons-round">search</span>
      <input type="text" placeholder="অর্ডার, মেনু খুঁজুন..."/>
    </div>
    <div class="topnav-right d-flex align-items-center gap-1 ms-auto">
      <!-- Online toggle -->
      <div class="d-flex align-items-center gap-2 px-2">
        <span style="font-size:.75rem;font-weight:700;color:var(--green)">● Online</span>
        <div class="form-check form-switch mb-0">
          <input class="form-check-input" type="checkbox" checked style="cursor:pointer;width:36px;height:20px;--bs-form-switch-bg:url(\"data:image/svg+xml,...\")"/>
        </div>
      </div>
      <div class="dropdown">
        <button class="icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside">
          <span class="material-icons-round">notifications</span>
          <span class="notif-badge">7</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end notif-dropdown-menu">
          <li><div class="notif-header"><h6>নোটিফিকেশন</h6></div></li>
          <li><a class="notif-item" href="#"><div class="notif-icon cart"><span class="material-icons-round">shopping_bag</span></div><div class="notif-text"><strong>নতুন অর্ডার #KK2615</strong><span>এইমাত্র</span></div></a></li>
          <li><a class="notif-item" href="#"><div class="notif-icon podcast"><span class="material-icons-round">payments</span></div><div class="notif-text"><strong>পেমেন্ট ৳37,664 সম্পন্ন</strong><span>1 ঘন্টা আগে</span></div></a></li>
          <li><div class="notif-footer"><a href="#">সব দেখুন</a></div></li>
        </ul>
      </div>
      <div class="dropdown">
        <img src="https://i.pravatar.cc/80?img=12" class="topnav-avatar" data-bs-toggle="dropdown" data-bs-auto-close="outside" alt="Vendor"/>
        <div class="dropdown-menu dropdown-menu-end user-dropdown-menu">
          <div class="user-info-block">
            <img src="https://i.pravatar.cc/80?img=12" class="user-avatar-lg" alt="Vendor"/>
            <div><div class="user-name">মা'র রান্নাঘর <span class="badge-pro">Vendor</span></div><a href="#" class="user-email">maa@example.com</a></div>
          </div>
          <hr class="dropdown-sep"/>
          <div class="ud-item"><a href="#" class="ud-link signout"><span class="d-flex align-items-center"><span class="material-icons-round ud-icon">logout</span>লগআউট</span></a></div>
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
    <a href="vendor-dashboard.html" class="mob-nav-item active"><span class="material-icons-round">dashboard</span><span>Dashboard</span></a>
    <a href="vendor-orders.html" class="mob-nav-item"><span class="material-icons-round">shopping_bag</span><span>অর্ডার</span></a>
    <a href="vendor-menu.html" class="mob-nav-item"><span class="material-icons-round">restaurant_menu</span><span>মেনু</span></a>
    <a href="vendor-earnings.html" class="mob-nav-item"><span class="material-icons-round">payments</span><span>আয়</span></a>
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

@stack('scripts')
@livewireScripts
</body>
</html>
