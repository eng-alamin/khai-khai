<div>

  {{-- ORDER SUMMARY CARD --}}
  <div class="card mb-4">
    <div class="card-header-kk">
      <div>
        <div class="card-title">Order Tracking</div>
        <div class="card-sub">Order #{{ $order['id'] }} • {{ $order['restaurant'] }}</div>
      </div>
      <span class="badge-kk badge-pink" style="font-size:13px;">{{ $order['statusLabel'] }}</span>
    </div>
    <div class="alert-kk alert-success-kk" style="margin-bottom:0;">
      🛵 {{ $rider['name'] }} is on the way — estimated {{ $order['eta'] }}
    </div>
  </div>

  <div class="row g-3">

    {{-- DELIVERY STATUS --}}
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-title mb-3">Delivery Status</div>
        <div class="track-wrap">
          @foreach($steps as $step)
          <div class="track-step {{ $step['state'] }}">
            <div class="track-dot">
              <i class="fa {{ $step['icon'] }}"></i>
            </div>
            <div class="track-text">
              <div class="t-title">{{ $step['label'] }}</div>
              <div class="t-sub">{{ $step['time'] }}</div>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- RIDER INFO --}}
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-title mb-3">Rider Info</div>
        <div class="text-center py-3">
          <img
            src="{{ $rider['photo'] }}"
            style="width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:12px; border:3px solid var(--pink);"
            alt="{{ $rider['name'] }}"
          >
          <div style="font-size:20px; font-weight:800;">{{ $rider['name'] }}</div>
          <div style="color:var(--text-3); font-size:13px; margin:4px 0 8px;">
            {{ $rider['zone'] }} • {{ $rider['vehicle'] }}
          </div>
          <div style="font-size:18px; color:var(--warning); font-weight:800; margin-bottom:16px;">
            ★ {{ number_format($rider['rating'], 1) }}
          </div>
          <div class="d-flex gap-2 justify-content-center">
            <button
              class="btn-kk btn-primary-kk btn-sm-kk"
              wire:click="callRider"
              wire:loading.attr="disabled"
              wire:target="callRider"
            >
              <i class="fa fa-phone"></i> Call
            </button>
            <button
              class="btn-kk btn-ghost-kk btn-sm-kk"
              wire:click="messageRider"
              wire:loading.attr="disabled"
              wire:target="messageRider"
            >
              <i class="fa fa-comment"></i> Message
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>

</div>