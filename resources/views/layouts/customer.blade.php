{{-- resources/views/layouts/customer.blade.php --}}
<!doctype html>
<html lang="bn">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>{{ $title ?? 'KhaiKhai Food Delivery' }}</title>

  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  @livewireStyles
  <style>
    :root {
      --pink: #e91e8c; --pink-dark: #c0167a; --pink-light: #ff4dab;
      --pink-soft: #fde8f4; --pink-ultra-soft: #fff5fb; --pink-mid: #f9c5e3;
      --accent: #ff6b35; --accent2: #7c3aed; --success: #10b981;
      --warning: #f59e0b; --danger: #ef4444; --info: #3b82f6;
      --dark: #1e1b4b; --dark2: #2d2a5e; --sidebar-w: 260px;
      --topbar-h: 64px; --radius: 12px; --radius-sm: 8px;
      --shadow: 0 4px 24px rgba(233,30,140,0.1);
      --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
      --bg: #f8f5ff; --card: #ffffff; --border: #f0e6f6;
      --text: #1e1b4b; --text-2: #6b7280; --text-3: #9ca3af;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    html { scroll-behavior:smooth; }
    body { font-family:"Hind Siliguri","Nunito",sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }

    /* SIDEBAR */
    .sidebar { width:var(--sidebar-w); background:var(--dark); display:flex; flex-direction:column; height:100vh; position:fixed; left:0; top:0; z-index:200; overflow-y:auto; }
    .sidebar-brand { padding:20px 20px 16px; border-bottom:1px solid rgba(255,255,255,0.08); }
    .brand-name { font-family:"Nunito",sans-serif; font-size:24px; font-weight:900; color:#fff; letter-spacing:-0.5px; }
    .brand-name span { color:var(--pink-light); }
    .mode-badge { display:inline-block; font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; background:var(--pink); color:#fff; margin-top:4px; text-transform:uppercase; letter-spacing:0.5px; }
    .sidebar-user { padding:16px 20px; border-bottom:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; gap:12px; }
    .sidebar-avatar { width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,var(--pink),var(--accent)); display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:700; color:#fff; flex-shrink:0; }
    .sidebar-user .uname { font-size:14px; font-weight:700; color:#fff; }
    .sidebar-user .urole { font-size:11px; color:rgba(255,255,255,0.5); margin-top:1px; }
    .sidebar-nav { flex:1; padding:12px 0; }
    .nav-section { font-size:10px; font-weight:700; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:1px; padding:12px 20px 4px; }
    .nav-item { display:flex; align-items:center; gap:10px; padding:10px 20px; color:rgba(255,255,255,0.65); font-size:14px; font-weight:500; cursor:pointer; border-radius:0; transition:all 0.2s; text-decoration:none; }
    .nav-item:hover, .nav-item.active { background:rgba(233,30,140,0.15); color:var(--pink-light); }
    .nav-item.active { border-left:3px solid var(--pink-light); }
    .nav-item i { width:18px; text-align:center; }
    .nav-badge { margin-left:auto; background:var(--pink); color:#fff; font-size:10px; font-weight:700; padding:1px 6px; border-radius:10px; }

    /* TOPBAR */
    .topbar { height:var(--topbar-h); background:var(--card); border-bottom:1px solid var(--border); display:flex; align-items:center; padding:0 24px; position:fixed; top:0; left:var(--sidebar-w); right:0; z-index:100; box-shadow:var(--shadow-sm); gap:12px; }
    .topbar-title { font-size:18px; font-weight:800; flex:1; }
    .topbar-actions { display:flex; align-items:center; gap:8px; }
    .topbar-btn { width:36px; height:36px; border-radius:10px; border:none; background:var(--bg); color:var(--text-2); cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:14px; transition:all 0.2s; text-decoration:none; }
    .topbar-btn:hover { background:var(--pink-soft); color:var(--pink); }
    .top-avatar { width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,var(--pink),var(--accent)); display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; color:#fff; cursor:pointer; }
    .topbar-btn .dot { position:absolute; top:5px; right:5px; width:5px; height:5px; border-radius:50%; background:var(--pink); color:#fff }

    /* MAIN */
    .main-wrap { margin-left:var(--sidebar-w); padding-top:var(--topbar-h); min-height:100vh; }
    .page-content { padding:24px; }

    /* CARDS */
    .card { background:var(--card); border-radius:var(--radius); border:1px solid var(--border); padding:20px; box-shadow:var(--shadow-sm); }
    .card-header-kk { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
    .card-title { font-size:16px; font-weight:800; }
    .card-sub { font-size:12px; color:var(--text-3); margin-top:2px; }

    /* BUTTONS */
    .btn-kk { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:var(--radius-sm); border:none; font-family:inherit; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s; text-decoration:none; }
    .btn-primary-kk { background:linear-gradient(135deg,var(--pink),var(--pink-light)); color:#fff; }
    .btn-primary-kk:hover { opacity:0.9; transform:translateY(-1px); }
    .btn-ghost-kk { background:var(--bg); color:var(--text-2); }
    .btn-ghost-kk:hover { background:var(--pink-soft); color:var(--pink); }
    .btn-outline-kk { background:transparent; border:1.5px solid var(--pink); color:var(--pink); }
    .btn-sm-kk { padding:5px 12px; font-size:12px; }

    /* BADGES */
    .badge-kk { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
    .badge-pink { background:var(--pink-soft); color:var(--pink); }
    .badge-green { background:#d1fae5; color:#065f46; }
    .badge-red { background:#fee2e2; color:#991b1b; }
    .badge-orange { background:#fff7ed; color:#c2410c; }

    /* ALERTS */
    .alert-kk { padding:12px 16px; border-radius:var(--radius-sm); font-size:13px; margin-bottom:16px; }
    .alert-pink-kk { background:var(--pink-soft); color:var(--pink-dark); border:1px solid var(--pink-mid); }
    .alert-success-kk { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }

    /* FORMS */
    .form-group { margin-bottom:16px; }
    .form-label-kk { display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px; }
    .form-control-kk { width:100%; padding:10px 14px; border:1.5px solid var(--border); border-radius:var(--radius-sm); font-family:inherit; font-size:14px; color:var(--text); background:var(--card); transition:border 0.2s; }
    .form-control-kk:focus { outline:none; border-color:var(--pink); }

    /* STAT CARDS */
    .stat-card { background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: var(--shadow-sm); position: relative; overflow: hidden; }
    .stat-card::after { content: ""; position: absolute; right: -10px; bottom: -10px; width: 70px; height: 70px; border-radius: 50%; background: currentColor; opacity: 0.06; }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .stat-info .num { font-size: 24px; font-weight: 800; line-height: 1; font-family: "Nunito", sans-serif; }
    .stat-info .label { font-size: 12px; color: var(--text-3); margin-top: 4px; }
    .stat-info .change { font-size: 11px; font-weight: 600; margin-top: 6px; display: flex; align-items: center; gap: 4px; }
    .change.up { color: var(--success); }
    .change.down { color: var(--danger); }

    .sc-pink .stat-icon { background: var(--pink-soft); color: var(--pink); }
    .sc-pink .num { color: var(--pink); }
    .sc-green .stat-icon { background: #d1fae5; color: var(--success); }
    .sc-green .num { color: var(--success); }
    .sc-orange .stat-icon { background: #fef3c7; color: var(--warning); }
    .sc-orange .num { color: var(--warning); }
    .sc-blue .stat-icon { background: #dbeafe; color: var(--info); }
    .sc-blue .num { color: var(--info); }
    .sc-purple .stat-icon { background: #ede9fe; color: var(--accent2); }
    .sc-purple .num { color: var(--accent2); }

    /* RESTAURANT CARD */
    .rest-card { background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; cursor: pointer; transition: all 0.2s; box-shadow: var(--shadow-sm); }
    .rest-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
    .rest-thumb { position:relative; }
    .rest-img { width: 100%; height: 140px; object-fit: cover; display: block; }
    .rest-tag { position: absolute; top: 10px; left: 10px; font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px; background: var(--pink); color: #fff; }
    .rest-info { padding: 14px; }
    .rest-name { font-size: 15px; font-weight: 700; margin-bottom: 6px; }
    .rest-meta { font-size:12px; color:var(--text-3); display:flex; gap:12px; }
    .rest-rating { color:var(--warning); font-weight:700; }


    /* FOOD CARD */
    .food-card { background:var(--card); border-radius:var(--radius); border:1px solid var(--border); overflow:hidden; transition:all 0.25s; box-shadow:var(--shadow-sm); }
    .food-card:hover { transform:translateY(-2px); box-shadow:var(--shadow); }
    .food-img { width:100%; height:120px; object-fit:cover; }
    .food-body { padding:10px; }
    .food-rest { font-size:11px; color:var(--text-3); margin-bottom:3px; }
    .food-name { font-size:13px; font-weight:700; margin-bottom:6px; }
    .food-footer { display:flex; align-items:center; justify-content:space-between; }
    .food-price { font-size:14px; font-weight:900; color:var(--pink); font-family:"Nunito",sans-serif; }
    .food-add { width:28px; height:28px; border-radius:50%; background:var(--pink); color:#fff; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:14px; transition:all 0.2s; }
    .food-add:hover { background:var(--pink-dark); transform:scale(1.1); }

    /* ORDER CARD */
    .order-card { background:var(--card); border-radius:var(--radius); border:1px solid var(--border); padding:16px; box-shadow:var(--shadow-sm); }
    .order-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
    .order-id { font-size:15px; font-weight:800; font-family:"Nunito",sans-serif; }
    .order-meta { display:flex; gap:16px; font-size:12px; color:var(--text-3); margin-bottom:8px; }
    .order-items { font-size:13px; color:var(--text-2); margin-bottom:12px; }
    .order-footer { display:flex; align-items:center; justify-content:space-between; }
    .order-total { font-size:16px; font-weight:900; color:var(--pink); font-family:"Nunito",sans-serif; }

    /* CATEGORY PILLS */
    .cat-pills { display:flex; gap:8px; flex-wrap:nowrap; overflow-x:auto; padding-bottom:4px; margin-bottom:20px; }
    .cat-pills::-webkit-scrollbar { height:0; }
    .cat-pill { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:30px; background:var(--card); border:1.5px solid var(--border); font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; transition:all 0.2s; }
    .cat-pill.active, .cat-pill:hover { background:var(--pink); color:#fff; border-color:var(--pink); }
    .cat-pill .emoji { font-size:16px; }

    /* HERO */
    .hero-banner { background: linear-gradient(135deg, #3d0a6e 0%, #7c1e8c 50%, var(--pink) 100%); border-radius: 16px; padding: 36px 32px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; position: relative; overflow: hidden; }
    .hero-banner::before { content: ""; position: absolute; right: -40px; top: -40px; width: 250px; height: 250px; border-radius: 50%; background: rgba(255, 255, 255, 0.06); }
    .hero-banner::after { content: ""; position: absolute; left: 40%; bottom: -30px; width: 180px; height: 180px; border-radius: 50%; background: rgba(255, 255, 255, 0.04); }
    .hero-text h2 { font-size: 28px; font-weight: 900; color: #fff; line-height: 1.2; font-family: "Nunito", sans-serif; }
    .hero-text h2 span { color: #ffd700; }
    .hero-text p { color: rgba(255, 255, 255, 0.75); font-size: 14px; margin: 8px 0 20px; }
    .hero-img { width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 4px solid rgba(255, 255, 255, 0.3); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3); flex-shrink: 0; }
    .hero-search { display: flex; background: #fff; border-radius: 14px; overflow: hidden; max-width: 420px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2); }
    .hero-search input { flex: 1; border: none; outline: none; padding: 12px 18px; font-size: 14px; font-family: inherit; }
    .hero-search button { background: var(--pink); color: #fff; border: none; padding: 12px 22px; font-size: 14px; font-weight: 700; cursor: pointer; font-family: inherit; }
    /* ===== CART BUTTON ===== */
    .cart-btn { position:fixed; bottom:80px; right:20px; z-index:400; width:54px; height:54px; border-radius:50%; background:var(--pink); color:#fff; border:none; align-items:center; justify-content:center; font-size:22px; cursor:pointer; box-shadow:0 4px 20px rgba(233,30,140,0.4); }
    .cart-count { position:absolute; top:-2px; right:-2px; width:20px; height:20px; border-radius:50%; background:var(--dark); color:#fff; font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; border:2px solid #fff; }

    /* ===== CART SIDEBAR ===== */
    .cart-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1000; opacity:0; visibility:hidden; transition:all 0.3s; }
    .cart-overlay.open { opacity:1; visibility:visible; }
    .cart-drawer { position:fixed; right:-360px; top:0; width:340px; height:100vh; background:var(--card); z-index:1001; box-shadow:-4px 0 30px rgba(0,0,0,0.15); transition:right 0.35s ease; display:flex; flex-direction:column; }
    .cart-overlay.open .cart-drawer { right:0; }
    .cart-head { padding:20px 24px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
    .cart-head h3 { font-size:18px; font-weight:800; }
    .cart-close { background:none; border:none; font-size:20px; cursor:pointer; color:var(--text-2); }
    .cart-items { flex:1; overflow-y:auto; padding:16px; }
    .cart-item { display:flex; gap:12px; align-items:center; padding:12px 0; border-bottom:1px solid var(--border); }
    .cart-item:last-child { border-bottom:none; }
    .cart-item .ci-img { width:44px; height:44px; border-radius:10px; object-fit:cover; flex-shrink:0; }
    .cart-item .ci-name { font-size:13px; font-weight:600; flex:1; }
    .cart-item .ci-price { font-size:14px; font-weight:800; color:var(--pink); font-family:"Nunito",sans-serif; }
    .cart-qty { display:flex; align-items:center; gap:8px; margin-top:4px; }
    .cart-qty button { width:24px; height:24px; border-radius:6px; background:var(--bg); border:1px solid var(--border); cursor:pointer; font-size:14px; display:flex; align-items:center; justify-content:center; }
    .cart-qty span { font-size:13px; font-weight:700; min-width:16px; text-align:center; }
    .cart-footer { padding:20px 24px; border-top:1px solid var(--border); }
    .cart-total-row { display:flex; justify-content:space-between; margin-bottom:6px; font-size:13px; color:var(--text-2); }
    .cart-grand { display:flex; justify-content:space-between; font-size:18px; font-weight:800; margin:12px 0; }
    .cart-grand span:last-child { color:var(--pink); font-family:"Nunito",sans-serif; }

    /* TRACK */
    .track-wrap { display:flex; flex-direction:column; gap:0; }
    .track-step { display:flex; align-items:flex-start; gap:12px; padding-bottom:20px; position:relative; }
    .track-step:not(:last-child)::before { content:""; position:absolute; left:15px; top:32px; bottom:0; width:2px; background:var(--border); }
    .track-step.done::before { background:var(--success); }
    .track-dot { width:32px; height:32px; border-radius:50%; border:2px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:12px; color:var(--text-3); background:var(--card); flex-shrink:0; z-index:1; }
    .track-step.done .track-dot { background:var(--success); border-color:var(--success); color:#fff; }
    .track-step.current .track-dot { background:var(--pink); border-color:var(--pink); color:#fff; animation:pulse 1.5s infinite; }
    .t-title { font-size:14px; font-weight:700; }
    .t-sub { font-size:12px; color:var(--text-3); margin-top:2px; }

    /* SIDEBAR OVERLAY */
    .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:199; }
    .sidebar-overlay.active { display:block; }

    /* MOBILE NAV */
    .mob-nav { display:none; }

    @keyframes pulse { 0%,100%{box-shadow:0 0 0 0 rgba(233,30,140,0.4)} 50%{box-shadow:0 0 0 8px rgba(233,30,140,0)} }

    /* TOAST */
    #toast-wrap { position:fixed; bottom:24px; right:24px; z-index:9999; display:flex; flex-direction:column; gap:8px; }
    .toast-item { background:var(--dark); color:#fff; padding:12px 20px; border-radius:12px; font-size:14px; font-weight:600; box-shadow:0 8px 32px rgba(0,0,0,0.2); animation:toastIn 0.3s ease; display:flex; align-items:center; gap:10px; }
    .toast-item.success { background:#065f46; }
    .toast-item.danger  { background:#991b1b; }
    .toast-item.info    { background:#1e40af; }
    @keyframes toastIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }

    @media(max-width:768px) {
      .sidebar { transform:translateX(-100%); transition:transform 0.3s; }
      .sidebar.open { transform:none; }
      .topbar { left:0; }
      .main-wrap { margin-left:0; padding-bottom:70px; }
      .mob-nav { display:flex; position:fixed; bottom:0; left:0; right:0; background:var(--card); border-top:1px solid var(--border); z-index:150; padding:6px 0; }
      .mob-nav-item { flex:1; display:flex; flex-direction:column; align-items:center; gap:3px; padding:6px; cursor:pointer; color:var(--text-3); font-size:10px; font-weight:600; transition:color 0.2s; text-decoration:none; }
      .mob-nav-item i { font-size:18px; }
      .mob-nav-item.active { color:var(--pink); }
      .hero-banner { flex-direction: column; gap: 20px; padding: 24px 20px; }
      .hero-img { width: 80px; height: 80px; }
      .hero-text h2 { font-size: 22px; }
      .hero-search { max-width: 100%; }

      /* STAT CARDS */
      .stat-info .num { font-size:14px;}
      .stat-info .label { font-size:9px; }
      .stat-info .change { font-size:8px;}
    }
  </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-name"><span>Khai</span>Khai</div>
    <div class="mode-badge">Customer</div>
  </div>
  <div class="sidebar-user">
    <div class="sidebar-avatar">{{ mb_substr(auth()->user()->name ?? 'র', 0, 1) }}</div>
    <div>
      <div class="uname">{{ auth()->user()->name ?? 'রাহেলা বেগম' }}</div>
      <div class="urole">Customer Account</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">মেনু</div>
    <a href="{{ route('customer.home') }}"        class="nav-item {{ request()->routeIs('customer.home') ? 'active' : '' }}">
      <i class="fa fa-home"></i> হোম
    </a>
    <a href="{{ route('customer.restaurants') }}" class="nav-item {{ request()->routeIs('customer.restaurants') ? 'active' : '' }}">
      <i class="fa fa-store"></i> রেস্তোরাঁ
    </a>
    <a href="{{ route('customer.items') }}"        class="nav-item {{ request()->routeIs('customer.items') ? 'active' : '' }}">
      <i class="fa fa-utensils"></i> খাবারের মেনু
    </a>
    <a href="{{ route('customer.orders') }}"      class="nav-item {{ request()->routeIs('customer.orders') ? 'active' : '' }}">
      <i class="fa fa-clipboard-list"></i> আমার অর্ডার
      <span class="nav-badge">3</span>
    </a>
    <a href="{{ route('customer.track') }}"       class="nav-item {{ request()->routeIs('customer.track') ? 'active' : '' }}">
      <i class="fa fa-map-marker-alt"></i> অর্ডার ট্র্যাক
    </a>
    <div class="nav-section">আমার অ্যাকাউন্ট</div>
    <a href="{{ route('customer.profile') }}"     class="nav-item {{ request()->routeIs('customer.profile') ? 'active' : '' }}">
      <i class="fa fa-user"></i> প্রোফাইল
    </a>
    <a href="{{ route('customer.addresses') }}"   class="nav-item {{ request()->routeIs('customer.addresses') ? 'active' : '' }}">
      <i class="fa fa-map-pin"></i> ঠিকানা
    </a>
    <a href="{{ route('customer.offers') }}"      class="nav-item {{ request()->routeIs('customer.offers') ? 'active' : '' }}">
      <i class="fa fa-tag"></i> অফার ও কুপন
    </a>
    <div class="nav-section">সাহায্য</div>
    <a href="{{ route('customer.support') }}" class="nav-item">
      <i class="fa fa-headset"></i> সাপোর্ট
    </a>
    <a href="{{ route('logout') }}" class="nav-item"
      onclick="event.preventDefault(); document.getElementById('logout-form').submit()">
        <i class="fa fa-sign-out-alt"></i> লগআউট
    </a>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
        @csrf
    </form>
  </nav>
</aside>

{{-- TOPBAR --}}
<header class="topbar">
  <button class="topbar-btn d-md-none" onclick="openSidebar()">
    <i class="fa fa-bars"></i>
  </button>

  <div class="topbar-title">{{ $breadcrumbTitle ?? 'হোম' }}</div>

  <div class="topbar-actions">
    <a href="{{ route('customer.items') }}" class="topbar-btn">
      <i class="fa fa-search"></i>
    </a>

    <a href="{{ route('customer.orders') }}" class="topbar-btn position-relative">
      <i class="fa fa-shopping-bag"></i>
      <span class="dot"></span>
    </a>

    <a href="{{ route('customer.support') }}" class="topbar-btn position-relative">
      <i class="fa fa-bell"></i>
      <span class="dot"></span>
    </a>
    
    <div class="top-avatar">{{ mb_substr(auth()->user()->name ?? 'র', 0, 1) }}</div>
  </div>
</header>

{{-- MOBILE NAV --}}
<nav class="mob-nav">
  <a href="{{ route('customer.home') }}"        class="mob-nav-item {{ request()->routeIs('customer.home') ? 'active' : '' }}">
    <i class="fa fa-home"></i>হোম
  </a>
  <a href="{{ route('customer.restaurants') }}" class="mob-nav-item {{ request()->routeIs('customer.restaurants') ? 'active' : '' }}">
    <i class="fa fa-store"></i>রেস্তোরাঁ
  </a>
  <a href="{{ route('customer.items') }}"        class="mob-nav-item {{ request()->routeIs('customer.items') ? 'active' : '' }}">
    <i class="fa fa-utensils"></i>মেনু
  </a>
  <a href="{{ route('customer.orders') }}"      class="mob-nav-item {{ request()->routeIs('customer.orders') ? 'active' : '' }}">
    <i class="fa fa-clipboard-list"></i>অর্ডার
  </a>
  <a href="{{ route('customer.profile') }}"     class="mob-nav-item {{ request()->routeIs('customer.profile') ? 'active' : '' }}">
    <i class="fa fa-user"></i>প্রোফাইল
  </a>
</nav>

{{-- TOAST --}}
<div id="toast-wrap"></div>

{{-- MAIN --}}
<main class="main-wrap">
  <div class="page-content">
    {{ $slot }}
  </div>
</main>

{{-- Cart component --}}
<livewire:customer.cart-component />

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('active');
  }
  function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('active');
  }
  function showToast(msg, type = '') {
    const w = document.getElementById('toast-wrap');
    const t = document.createElement('div');
    t.className = 'toast-item ' + (type || '');
    t.textContent = msg;
    w.appendChild(t);
    setTimeout(() => t.remove(), 3000);
  }
  
  document.addEventListener('livewire:initialized', () => {
    Livewire.on('order-placed', () => {
      showToast('✅ অর্ডার সফলভাবে দেওয়া হয়েছে!', 'success');
    });
  });
</script>
@livewireScripts
@stack('scripts')
</body>
</html>