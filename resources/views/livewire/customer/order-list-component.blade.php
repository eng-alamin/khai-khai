<div>
  {{-- PAGE HEADER --}}
  <div class="card-header-kk mb-4" style="align-items:flex-start;">
    <div>
      <div class="card-title" style="font-size:22px;">আমার অর্ডার</div>
      <div class="card-sub">মোট {{ count($filteredOrders) }}টি অর্ডার</div>
    </div>
    <a href="{{ route('customer.items') }}" class="btn-kk btn-primary-kk">
      <i class="fa fa-plus"></i> নতুন অর্ডার
    </a>
  </div>

  {{-- FILTER TABS --}}
  <div class="cat-pills mb-4">
    <button class="cat-pill active" onclick="filterOrders('all', this)">
      <span class="emoji">📋</span> সব অর্ডার
    </button>
    <button class="cat-pill" onclick="filterOrders('pending', this)">
      <span class="emoji">⏳</span> পেন্ডিং
    </button>
    <button class="cat-pill" onclick="filterOrders('processing', this)">
      <span class="emoji">🍳</span> প্রস্তুত হচ্ছে
    </button>
    <button class="cat-pill" onclick="filterOrders('on_the_way', this)">
      <span class="emoji">🛵</span> যাচ্ছে
    </button>
    <button class="cat-pill" onclick="filterOrders('delivered', this)">
      <span class="emoji">✅</span> ডেলিভারড
    </button>
    <button class="cat-pill" onclick="filterOrders('cancelled', this)">
      <span class="emoji">❌</span> বাতিল
    </button>
  </div>

  {{-- SEARCH BAR --}}
  <div class="card mb-4" style="padding:14px 18px;">
    <div style="display:flex; gap:10px; align-items:center;">
      <i class="fa fa-search" style="color:var(--text-3);"></i>
      <input
        type="text"
        class="form-control-kk"
        id="orderSearch"
        placeholder="অর্ডার ID বা রেস্তোরাঁর নাম দিয়ে খুঁজুন..."
        style="border:none; padding:0; font-size:14px;"
        oninput="searchOrders(this.value)"
      />
    </div>
  </div>

  {{-- ORDER LIST --}}
  <div id="orderList" style="display:flex; flex-direction:column; gap:16px;">

    @forelse($filteredOrders as $order)
    <div
      class="order-card order-item"
      data-status="{{ $order['status'] }}"
      data-id="{{ strtolower($order['id']) }}"
      data-restaurant="{{ strtolower($order['rest']) }}"
    >
      {{-- ORDER HEADER --}}
      <div class="order-header">
        <div>
          <div class="order-id" style="color:var(--pink);">{{ $order['id'] }}</div>
        </div>
        <div>
          @php
            $statusMap = [
              'pending'    => ['label' => 'পেন্ডিং',        'class' => 'badge-orange'],
              'processing' => ['label' => 'প্রস্তুত হচ্ছে', 'class' => 'badge-pink'],
              'on_the_way' => ['label' => 'যাচ্ছে',         'class' => 'badge-pink'],
              'delivered'  => ['label' => 'ডেলিভারড',       'class' => 'badge-green'],
              'cancelled'  => ['label' => 'বাতিল',           'class' => 'badge-red'],
            ];
            $s = $statusMap[$order['status']] ?? ['label' => $order['statusLabel'], 'class' => 'badge-pink'];
          @endphp
          <span class="badge-kk {{ $s['class'] }}">{{ $s['label'] }}</span>
        </div>
      </div>

      {{-- META --}}
      <div class="order-meta">
        <span><i class="fa fa-store" style="margin-right:4px;"></i>{{ $order['rest'] }}</span>
        <span><i class="fa fa-clock" style="margin-right:4px;"></i>{{ $order['time'] }}</span>
      </div>

      {{-- ITEMS SUMMARY --}}
      <div class="order-items">
        {{ $order['items'] }}
      </div>

      {{-- FOOTER --}}
      <div class="order-footer">
        <div class="order-total">৳{{ number_format($order['total'], 0) }}</div>
        <div style="display:flex; gap:8px;">

          {{-- Track button — only for active orders --}}
          @if(in_array($order['status'], ['pending', 'processing', 'on_the_way']))
          <a href="{{ route('customer.track', $order['id']) }}" class="btn-kk btn-outline-kk btn-sm-kk">
            <i class="fa fa-map-marker-alt"></i> ট্র্যাক
          </a>
          @endif

          {{-- Review button — only for delivered orders --}}
          @if($order['status'] === 'delivered')
          <button
            class="btn-kk btn-ghost-kk btn-sm-kk"
            style="border:1.5px solid var(--warning); color:var(--warning);"
            onclick="openReviewModal('{{ $order['id'] }}')"
          >
            <i class="fa fa-star"></i> রিভিউ
          </button>
          @endif

          {{-- Reorder button --}}
          @if(in_array($order['status'], ['delivered', 'cancelled']))
          <button
            class="btn-kk btn-ghost-kk btn-sm-kk"
            onclick="reorder('{{ $order['id'] }}')"
          >
            <i class="fa fa-redo"></i> পুনরায়
          </button>
          @endif

          {{-- Cancel button — only for pending orders --}}
          @if($order['status'] === 'pending')
          <button
            class="btn-kk btn-sm-kk"
            style="background:#fee2e2; color:#991b1b;"
            onclick="cancelOrder('{{ $order['id'] }}')"
          >
            <i class="fa fa-times"></i> বাতিল
          </button>
          @endif

        </div>
      </div>
    </div>

    @empty
    <div class="card" style="text-align:center; padding:48px 24px;">
      <div style="font-size:64px; margin-bottom:16px;">🍽️</div>
      <div style="font-size:18px; font-weight:800; margin-bottom:8px;">কোনো অর্ডার নেই</div>
      <div style="color:var(--text-3); font-size:14px; margin-bottom:20px;">আপনি এখনো কোনো অর্ডার করেননি।</div>
      <a href="{{ route('customer.items') }}" class="btn-kk btn-primary-kk" style="margin:0 auto;">
        <i class="fa fa-utensils"></i> এখনই অর্ডার করুন
      </a>
    </div>
    @endforelse

  </div>

  {{-- EMPTY FILTER STATE (hidden by default) --}}
  <div id="noFilterResult" style="display:none; text-align:center; padding:48px 24px;">
    <div style="font-size:48px; margin-bottom:12px;">🔍</div>
    <div style="font-size:16px; font-weight:700;">কোনো অর্ডার পাওয়া যায়নি</div>
    <div style="color:var(--text-3); font-size:13px; margin-top:6px;">অন্য ফিল্টার বা সার্চ শব্দ ব্যবহার করুন।</div>
  </div>

  {{-- REVIEW MODAL --}}
  <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius:var(--radius); border:1px solid var(--border); box-shadow:var(--shadow);">
        <div class="modal-header" style="border-bottom:1px solid var(--border); padding:20px 24px;">
          <h5 class="modal-title" style="font-weight:800;">⭐ রিভিউ দিন</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="padding:24px;">
          <input type="hidden" id="reviewOrderId">
          <div class="form-group">
            <label class="form-label-kk">রেটিং</label>
            <div id="starRating" style="display:flex; gap:6px; font-size:28px; cursor:pointer; margin-bottom:4px;">
              <span class="star" data-v="1">⭐</span>
              <span class="star" data-v="2">⭐</span>
              <span class="star" data-v="3">⭐</span>
              <span class="star" data-v="4">⭐</span>
              <span class="star" data-v="5">⭐</span>
            </div>
            <input type="hidden" id="ratingValue" value="0">
          </div>
          <div class="form-group">
            <label class="form-label-kk">মন্তব্য</label>
            <textarea
              class="form-control-kk"
              id="reviewComment"
              rows="3"
              placeholder="আপনার অভিজ্ঞতা শেয়ার করুন..."
            ></textarea>
          </div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--border); padding:16px 24px; gap:8px;">
          <button class="btn-kk btn-ghost-kk" data-bs-dismiss="modal">বাতিল</button>
          <button class="btn-kk btn-primary-kk" onclick="submitReview()">
            <i class="fa fa-paper-plane"></i> জমা দিন
          </button>
        </div>
      </div>
    </div>
  </div>

</div>

@push('scripts')
<script>
  /* ── Filter tabs ── */
  function filterOrders(status, btn) {
    document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');

    const items = document.querySelectorAll('.order-item');
    let visible = 0;
    items.forEach(card => {
      const show = status === 'all' || card.dataset.status === status;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    document.getElementById('noFilterResult').style.display = visible === 0 ? 'block' : 'none';
  }

  /* ── Search ── */
  function searchOrders(q) {
    q = q.toLowerCase().trim();
    const items = document.querySelectorAll('.order-item');
    let visible = 0;
    items.forEach(card => {
      const match = !q || card.dataset.id.includes(q) || card.dataset.restaurant.includes(q);
      card.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    document.getElementById('noFilterResult').style.display = visible === 0 ? 'block' : 'none';
  }

  /* ── Reorder ── */
  function reorder(orderId) {
    fetch(`/customer/orders/${orderId}/reorder`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        showToast('🛒 পুনরায় কার্টে যোগ করা হয়েছে!', 'success');
        setTimeout(() => toggleCart(), 600);
      } else {
        showToast(d.message || 'সমস্যা হয়েছে!', 'danger');
      }
    })
    .catch(() => showToast('নেটওয়ার্ক সমস্যা!', 'danger'));
  }

  /* ── Cancel order ── */
  function cancelOrder(orderId) {
    if (!confirm('আপনি কি নিশ্চিত এই অর্ডারটি বাতিল করতে চান?')) return;
    fetch(`/customer/orders/${orderId}/cancel`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        showToast('অর্ডার বাতিল করা হয়েছে।', 'info');
        setTimeout(() => location.reload(), 1000);
      } else {
        showToast(d.message || 'বাতিল করা যায়নি!', 'danger');
      }
    });
  }

  /* ── Review modal ── */
  let selectedRating = 0;

  function openReviewModal(orderId) {
    document.getElementById('reviewOrderId').value = orderId;
    document.getElementById('reviewComment').value = '';
    selectedRating = 0;
    updateStars(0);
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
  }

  document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('click', () => {
      selectedRating = parseInt(star.dataset.v);
      document.getElementById('ratingValue').value = selectedRating;
      updateStars(selectedRating);
    });
    star.addEventListener('mouseenter', () => updateStars(parseInt(star.dataset.v)));
    star.addEventListener('mouseleave', () => updateStars(selectedRating));
  });

  function updateStars(v) {
    document.querySelectorAll('.star').forEach(s => {
      s.style.opacity = parseInt(s.dataset.v) <= v ? '1' : '0.3';
    });
  }

  function submitReview() {
    const orderId = document.getElementById('reviewOrderId').value;
    const rating  = document.getElementById('ratingValue').value;
    const comment = document.getElementById('reviewComment').value.trim();

    if (rating < 1) { showToast('রেটিং দিন!', 'danger'); return; }

    fetch(`/customer/orders/${orderId}/review`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
      body: JSON.stringify({ rating, comment })
    })
    .then(r => r.json())
    .then(d => {
      bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
      showToast(d.success ? '⭐ রিভিউ জমা দেওয়া হয়েছে!' : (d.message || 'সমস্যা হয়েছে!'), d.success ? 'success' : 'danger');
    });
  }
</script>
@endpush