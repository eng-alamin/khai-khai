<div>
  {{-- PAGE HEADER --}}
  <div class="card-header-kk mb-4" style="align-items:flex-start;">
    <div>
      <div class="card-title" style="font-size:22px;">My Orders</div>
      <div class="card-sub">Total {{ $filteredOrders->total() }} orders</div>
    </div>
    <a href="{{ route('customer.items') }}" class="btn-kk btn-primary-kk">
      <i class="fa fa-plus"></i> New Order
    </a>
  </div>

  {{-- FILTER TABS --}}
  <div class="cat-pills mb-4">
    @foreach($filters as $filter)
    <button
      class="cat-pill {{ $activeFilter === $filter['key'] ? 'active' : '' }}"
      wire:click="setFilter('{{ $filter['key'] }}')"
    >
      <span class="emoji">{{ $filter['emoji'] }}</span> {{ $filter['label'] }}
    </button>
    @endforeach
  </div>

  {{-- SEARCH BAR --}}
  <div class="card mb-4" style="padding:14px 18px;">
    <div class="d-flex gap-2 align-items-center">
      <i class="fa fa-search text-muted"></i>
      <input
        type="text"
        class="form-control border-0 p-0"
        wire:model.live.debounce.300ms="searchQuery"
        placeholder="Search by Order ID or restaurant name..."
        style="font-size:14px; box-shadow:none;"
      />
    </div>
  </div>

  {{-- ORDER LIST --}}
  <div class="d-flex flex-column gap-3">

    @forelse($filteredOrders as $order)
    @php
      $statusMap = [
        'pending'    => ['label' => 'Pending',    'class' => 'badge-orange'],
        'confirmed'  => ['label' => 'Confirmed',  'class' => 'badge-pink'],
        'preparing'  => ['label' => 'Preparing',  'class' => 'badge-pink'],
        'picked_up'  => ['label' => 'On the Way', 'class' => 'badge-pink'],
        'delivered'  => ['label' => 'Delivered',  'class' => 'badge-green'],
        'cancelled'  => ['label' => 'Cancelled',  'class' => 'badge-red'],
      ];
      $s = $statusMap[$order->status] ?? ['label' => $order->status, 'class' => 'badge-pink'];
    @endphp

    <div class="order-card" wire:key="order-{{ $order->id }}">

      {{-- ORDER HEADER --}}
      <div class="order-header">
        <div class="order-id" style="color:var(--pink);">#{{ $order->order_number }}</div>
        <span class="badge-kk {{ $s['class'] }}">{{ $s['label'] }}</span>
      </div>

      {{-- META --}}
      <div class="order-meta">
        <span><i class="fa fa-store me-1"></i>{{ $order->restaurant->name }}</span>
        <span><i class="fa fa-clock me-1"></i>{{ $order->restaurant->avg_delivery_min }}-{{ $order->restaurant->avg_delivery_max }} minutes</span>
      </div>

      {{-- ITEMS SUMMARY --}}
      <div class="order-items">{{ $order->items_summary }}</div>

      {{-- FOOTER --}}
      <div class="order-footer">
        <div class="order-total">৳{{ number_format($order->total_amount, 0) }}</div>
        <div class="d-flex gap-2">

          {{-- Track — active orders only --}}
          @if(in_array($order->status, ['pending', 'confirmed', 'preparing', 'picked_up']))
          <a href="{{ route('customer.track', $order->order_number) }}" class="btn-kk btn-outline-kk btn-sm-kk">
            <i class="fa fa-map-marker-alt"></i> Track
          </a>
          @endif

          {{-- Review — delivered + not yet reviewed --}}
          @if($order->status === 'delivered' && !$order->review)
          <button
            class="btn-kk btn-ghost-kk btn-sm-kk"
            style="border:1.5px solid var(--warning); color:var(--warning);"
            wire:click="openReviewModal({{ $order->id }})"
            data-bs-toggle="modal"
            data-bs-target="#reviewModal"
          >
            <i class="fa fa-star"></i> Review
          </button>
          @elseif($order->status === 'delivered' && $order->review)
          <span class="btn-kk btn-sm-kk" style="background:#fff7ed; color:var(--warning); cursor:default;">
            <i class="fa fa-star"></i> {{ $order->review->rating }}/5
          </span>
          @endif

          {{-- Reorder — delivered or cancelled --}}
          @if(in_array($order->status, ['delivered', 'cancelled']))
          <button
            class="btn-kk btn-ghost-kk btn-sm-kk"
            wire:click="reorder({{ $order->id }})"
            wire:loading.attr="disabled"
            wire:target="reorder({{ $order->id }})"
          >
            <span wire:loading.remove wire:target="reorder({{ $order->id }})">
              <i class="fa fa-redo"></i> Reorder
            </span>
            <span wire:loading wire:target="reorder({{ $order->id }})">
              <i class="fa fa-spinner fa-spin"></i>
            </span>
          </button>
          @endif

          {{-- Cancel — pending only --}}
          @if($order->status === 'pending')
          <button
            class="btn-kk btn-sm-kk"
            style="background:#fee2e2; color:#991b1b;"
            wire:click="cancelOrder({{ $order->id }})"
            wire:confirm="Are you sure you want to cancel this order?"
            wire:loading.attr="disabled"
            wire:target="cancelOrder({{ $order->id }})"
          >
            <span wire:loading.remove wire:target="cancelOrder({{ $order->id }})">
              <i class="fa fa-times"></i> Cancel
            </span>
            <span wire:loading wire:target="cancelOrder({{ $order->id }})">
              <i class="fa fa-spinner fa-spin"></i>
            </span>
          </button>
          @endif

        </div>
      </div>
    </div>

    @empty
    <div class="card text-center py-5">
      <div style="font-size:64px;" class="mb-3">
        {{ $searchQuery ? '🔍' : '🍽️' }}
      </div>
      <div class="fw-bold fs-5 mb-2">
        {{ $searchQuery ? 'No orders found' : 'No orders yet' }}
      </div>
      <div class="text-muted small mb-4">
        {{ $searchQuery
          ? 'Try a different search term or filter.'
          : "You haven't placed any orders yet." }}
      </div>
      @if(!$searchQuery)
      <div>
        <a href="{{ route('customer.items') }}" class="btn-kk btn-primary-kk">
          <i class="fa fa-utensils"></i> Order Now
        </a>
      </div>
      @endif
    </div>
    @endforelse

  </div>

  {{-- PAGINATION --}}
  @if($filteredOrders->hasPages())
  <div class="mt-4">
    {{ $filteredOrders->links() }}
  </div>
  @endif

  {{-- REVIEW MODAL (Bootstrap 5) --}}
  <div
    class="modal fade"
    id="reviewModal"
    tabindex="-1"
    aria-labelledby="reviewModalLabel"
    aria-hidden="true"
    wire:ignore.self
  >
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius:var(--radius); border:1px solid var(--border); box-shadow:var(--shadow);">

        <div class="modal-header" style="border-bottom:1px solid var(--border);">
          <h5 class="modal-title fw-bold" id="reviewModalLabel">⭐ Leave a Review</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4">

          <div class="mb-3">
            <label class="form-label-kk mb-2">Rating</label>
            <div class="d-flex gap-2" style="font-size:32px; cursor:pointer;">
              @for($i = 1; $i <= 5; $i++)
              <span
                wire:click="setRating({{ $i }})"
                style="opacity:{{ $i <= $reviewRating ? '1' : '0.25' }}; transition:opacity .15s; user-select:none;"
              >⭐</span>
              @endfor
            </div>
            @error('reviewRating')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-1">
            <label class="form-label-kk mb-2">Comment</label>
            <textarea
              class="form-control"
              wire:model="reviewComment"
              rows="3"
              placeholder="Share your experience..."
              style="border-radius:var(--radius); border:1px solid var(--border); font-size:14px; resize:none;"
            ></textarea>
            @error('reviewComment')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>

        </div>

        <div class="modal-footer" style="border-top:1px solid var(--border);">
          <button type="button" class="btn-kk btn-ghost-kk" data-bs-dismiss="modal">Cancel</button>
          <button
            type="button"
            class="btn-kk btn-primary-kk"
            wire:click="submitReview"
            wire:loading.attr="disabled"
            wire:target="submitReview"
          >
            <span wire:loading.remove wire:target="submitReview">
              <i class="fa fa-paper-plane"></i> Submit
            </span>
            <span wire:loading wire:target="submitReview">
              <i class="fa fa-spinner fa-spin"></i> Submitting...
            </span>
          </button>
        </div>

      </div>
    </div>
  </div>

</div>

@push('scripts')
<script>
  document.addEventListener('livewire:init', () => {
    Livewire.on('show-toast', () => {
      const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
      if (modal) modal.hide();
    });
  });
</script>
@endpush