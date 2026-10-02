<div>

  {{-- ORDER SUMMARY CARD --}}
  <div class="card mb-4">
    <div class="card-header-kk">
      <div>
        <div class="card-title">Order Tracking</div>
        <div class="card-sub">Order #{{ $order->order_number }} • {{ $order->isAdminOrder() ? 'KhaiKhai Store' : ($order->restaurant->name ?? '—') }}</div>
      </div>
      <span class="badge-kk badge-pink" style="font-size:13px;">
        {{ ucwords(str_replace('_', ' ', $order->status)) }}
      </span>
    </div>

    @if($order->status === 'cancelled')
      <div class="alert-kk alert-danger-kk" style="margin-bottom:0;">
        ❌ এই অর্ডারটি বাতিল হয়ে গেছে।
        @if($order->cancel_reason)
          কারণ: {{ $order->cancel_reason }}
        @endif
      </div>
    @elseif($order->status === 'delivered')
      <div class="alert-kk alert-success-kk" style="margin-bottom:0;">
        ✅ অর্ডারটি সফলভাবে ডেলিভার হয়েছে।
      </div>
    @elseif($this->riderInfo)
      <div class="alert-kk alert-success-kk" style="margin-bottom:0;">
        🛵 {{ $this->riderInfo['name'] }} is on the way
        @if($order->estimated_delivery_at)
          — estimated {{ $order->estimated_delivery_at->format('g:i A') }}
        @endif
      </div>
    @endif
  </div>

  <div class="row g-3">

    {{-- DELIVERY STATUS --}}
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-title mb-3">Delivery Status</div>
        <div class="track-wrap">
          @foreach($this->steps as $step)
          <div class="track-step {{ $step['state'] }}">
            <div class="track-dot">
              <i class="fa {{ $step['icon'] }}"></i>
            </div>
            <div class="track-text">
              <div class="t-title">{{ $step['label'] }}</div>
              <div class="t-sub">{{ $step['time'] ?? '—' }}</div>
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

        @if($this->riderInfo)
          <div class="text-center py-3">
            @if($this->riderInfo['photo'])
              <img
                src="{{ $this->riderInfo['photo'] }}"
                style="width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:12px; border:3px solid var(--pink);"
                alt="{{ $this->riderInfo['name'] }}"
              >
            @endif
            <div style="font-size:20px; font-weight:800;">{{ $this->riderInfo['name'] }}</div>
            <div style="color:var(--text-3); font-size:13px; margin:4px 0 8px;">
              {{ $this->riderInfo['zone'] }} • {{ $this->riderInfo['vehicle'] }}
            </div>
            @if($this->riderInfo['rating'])
              <div style="font-size:18px; color:var(--warning); font-weight:800; margin-bottom:16px;">
                ★ {{ number_format($this->riderInfo['rating'], 1) }}
              </div>
            @endif
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
        @else
          <div class="text-center py-4" style="color:var(--text-3);">
            <i class="fa fa-motorcycle fa-2x mb-2"></i>
            <div>এখনো কোনো rider assign হয়নি।</div>
          </div>
        @endif
      </div>
    </div>

    {{-- LIVE RIDER LOCATION MAP --}}
    <div class="col-md-12">
      <div class="card">
        <div class="card-title mb-3">Live Location</div>

        @if($order->status === 'picked_up')
          {{-- Key must be "order" to match OrderTrackingMap::mount(Order $order) --}}
          @livewire('customer.order-tracking-map', ['order' => $order], key('order-map-'.$order->id))
          {{-- @livewire('customer.order-tracking-map', ['order' => $order->id], key('order-map-'.$order->id)) --}}
        @else
          <div class="text-center py-4" style="color:var(--text-3);">
            <i class="fa fa-map-marker-alt fa-2x mb-2"></i>
            <div>Rider picked up করলে এখানে live location map দেখা যাবে।</div>
          </div>
        @endif

      </div>
    </div>

  </div>

</div>