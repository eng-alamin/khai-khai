<div>

  {{-- PAGE HEADER --}}
  <div class="card-header-kk mb-4" style="align-items:flex-start;">
    <div>
      <div class="card-title" style="font-size:22px;">সাপোর্ট সেন্টার</div>
      <div class="card-sub">আমরা সাহায্য করতে সদা প্রস্তুত 🤝</div>
    </div>
  </div>

  {{-- QUICK CONTACT CARDS --}}
  <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; margin-bottom:28px;">

    <a href="tel:+8801XXXXXXXXX" class="card support-contact-card" style="text-align:center; padding:22px 16px; text-decoration:none; transition:transform .18s,box-shadow .18s;">
      <div style="font-size:32px; margin-bottom:10px;">📞</div>
      <div style="font-weight:800; font-size:14px; color:var(--text-1);">ফোন করুন</div>
      <div style="font-size:12px; color:var(--text-3); margin-top:4px;">সকাল ৮টা – রাত ১১টা</div>
    </a>

    <a href="https://wa.me/8801XXXXXXXXX" target="_blank" class="card support-contact-card" style="text-align:center; padding:22px 16px; text-decoration:none; transition:transform .18s,box-shadow .18s;">
      <div style="font-size:32px; margin-bottom:10px;">💬</div>
      <div style="font-weight:800; font-size:14px; color:var(--text-1);">WhatsApp</div>
      <div style="font-size:12px; color:var(--text-3); margin-top:4px;">দ্রুত রেসপন্স</div>
    </a>

    <a href="mailto:support@khaikhai.com" class="card support-contact-card" style="text-align:center; padding:22px 16px; text-decoration:none; transition:transform .18s,box-shadow .18s;">
      <div style="font-size:32px; margin-bottom:10px;">📧</div>
      <div style="font-weight:800; font-size:14px; color:var(--text-1);">ইমেইল</div>
      <div style="font-size:12px; color:var(--text-3); margin-top:4px;">২৪ ঘণ্টার মধ্যে</div>
    </a>

    <div class="card support-contact-card" onclick="openLiveChat()" style="text-align:center; padding:22px 16px; cursor:pointer; transition:transform .18s,box-shadow .18s; position:relative;">
      <div style="font-size:32px; margin-bottom:10px;">🟢</div>
      <div style="font-weight:800; font-size:14px; color:var(--text-1);">লাইভ চ্যাট</div>
      <div style="font-size:12px; color:var(--text-3); margin-top:4px;">এখন অনলাইন</div>
      <span style="position:absolute;top:12px;right:12px;width:8px;height:8px;border-radius:50%;background:#22c55e;display:block;box-shadow:0 0 0 2px #dcfce7;"></span>
    </div>

  </div>

  {{-- TICKET FORM --}}
  <div class="card mb-4" style="padding:24px;">
    <div style="font-size:17px; font-weight:800; margin-bottom:4px;">🎫 সাপোর্ট টিকেট পাঠান</div>
    <div style="font-size:13px; color:var(--text-3); margin-bottom:20px;">সমস্যা বিস্তারিত জানালে আমরা দ্রুত সমাধান দিতে পারব।</div>

    <div style="display:grid; gap:16px;">

      {{-- Category --}}
      <div>
        <label class="form-label-kk">সমস্যার ধরন <span style="color:var(--pink);">*</span></label>
        <select class="form-control-kk" id="ticketCategory" style="width:100%;">
          <option value="">— ধরন বেছে নিন —</option>
          <option value="order">অর্ডার সমস্যা</option>
          <option value="payment">পেমেন্ট সমস্যা</option>
          <option value="delivery">ডেলিভারি সমস্যা</option>
          <option value="food_quality">খাবারের মান</option>
          <option value="account">অ্যাকাউন্ট সমস্যা</option>
          <option value="refund">রিফান্ড অনুরোধ</option>
          <option value="other">অন্যান্য</option>
        </select>
      </div>

      {{-- Order ID (optional) --}}
      <div>
        <label class="form-label-kk">অর্ডার ID (যদি থাকে)</label>
        <input type="text" class="form-control-kk" id="ticketOrderId" placeholder="#KK2601" style="width:100%;" />
      </div>

      {{-- Subject --}}
      <div>
        <label class="form-label-kk">বিষয় <span style="color:var(--pink);">*</span></label>
        <input type="text" class="form-control-kk" id="ticketSubject" placeholder="সমস্যা সংক্ষেপে লিখুন..." style="width:100%;" />
      </div>

      {{-- Message --}}
      <div>
        <label class="form-label-kk">বিস্তারিত <span style="color:var(--pink);">*</span></label>
        <textarea class="form-control-kk" id="ticketMessage" rows="4"
          placeholder="কী হয়েছে তা বিস্তারিত লিখুন। যত বেশি তথ্য দেবেন, তত দ্রুত সমাধান পাবেন।"
          style="width:100%; resize:vertical;"></textarea>
        <div style="font-size:12px; color:var(--text-3); margin-top:6px; text-align:right;">
          <span id="charCount">০</span>/৫০০ অক্ষর
        </div>
      </div>

      {{-- Submit --}}
      <div>
        <button class="btn-kk btn-primary-kk" onclick="submitTicket()" style="width:100%; justify-content:center;">
          <i class="fa fa-paper-plane"></i> টিকেট পাঠান
        </button>
      </div>

    </div>
  </div>

  {{-- MY TICKETS --}}
  <div class="card mb-4" style="padding:24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
      <div style="font-size:17px; font-weight:800;">📋 আমার টিকেট</div>
      <span class="badge-kk badge-pink" style="font-size:12px;">{{ count($tickets ?? []) }}টি</span>
    </div>

    @forelse($tickets ?? [] as $ticket)
    <div style="border:1px solid var(--border); border-radius:var(--radius); padding:16px; margin-bottom:12px;">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px; margin-bottom:8px;">
        <div>
          <div style="font-weight:700; font-size:14px; color:var(--text-1);">{{ $ticket['subject'] }}</div>
          <div style="font-size:12px; color:var(--text-3); margin-top:3px;">#{{ $ticket['ticket_id'] }} · {{ $ticket['created_at'] }}</div>
        </div>
        @php
          $tStatusMap = [
            'open'        => ['label'=>'খোলা',      'class'=>'badge-orange'],
            'in_progress' => ['label'=>'প্রক্রিয়াধীন','class'=>'badge-pink'],
            'resolved'    => ['label'=>'সমাধান হয়েছে','class'=>'badge-green'],
            'closed'      => ['label'=>'বন্ধ',       'class'=>'badge-red'],
          ];
          $ts = $tStatusMap[$ticket['status']] ?? ['label'=>$ticket['status'],'class'=>'badge-pink'];
        @endphp
        <span class="badge-kk {{ $ts['class'] }}">{{ $ts['label'] }}</span>
      </div>
      <div style="font-size:13px; color:var(--text-2); line-height:1.5;">{{ Str::limit($ticket['message'], 80) }}</div>
      @if(!empty($ticket['reply']))
      <div style="margin-top:10px; padding:10px 14px; background:var(--bg-2,#f8f8f8); border-radius:8px; border-left:3px solid var(--pink);">
        <div style="font-size:11px; font-weight:700; color:var(--pink); margin-bottom:4px;">🤖 KhaiKhai সাপোর্ট:</div>
        <div style="font-size:13px; color:var(--text-2);">{{ $ticket['reply'] }}</div>
      </div>
      @endif
    </div>
    @empty
    <div style="text-align:center; padding:32px 16px;">
      <div style="font-size:40px; margin-bottom:10px;">📭</div>
      <div style="font-size:14px; color:var(--text-3);">এখনো কোনো টিকেট নেই।</div>
    </div>
    @endforelse
  </div>

  {{-- FAQ --}}
  <div class="card mb-4" style="padding:24px;">
    <div style="font-size:17px; font-weight:800; margin-bottom:20px;">❓ সাধারণ প্রশ্নোত্তর (FAQ)</div>

    @php
      $faqs = [
        ['q' => 'আমার অর্ডার কতক্ষণে আসবে?',         'a' => 'সাধারণত অর্ডার কনফার্মের ৩০–৪৫ মিনিটের মধ্যে ডেলিভারি হয়। ব্যস্ত সময়ে একটু বেশি লাগতে পারে।'],
        ['q' => 'অর্ডার বাতিল করব কীভাবে?',           'a' => 'অর্ডার "পেন্ডিং" অবস্থায় থাকলে My Orders পেজ থেকে বাতিল করা যাবে। রেস্তোরাঁ গ্রহণ করে নিলে সরাসরি সাপোর্টে যোগাযোগ করুন।'],
        ['q' => 'পেমেন্ট কেটে গেছে কিন্তু অর্ডার হয়নি?', 'a' => 'এ ক্ষেত্রে ৭২ ঘণ্টার মধ্যে স্বয়ংক্রিয়ভাবে রিফান্ড হয়। না হলে টিকেট পাঠান।'],
        ['q' => 'রিফান্ড পেতে কতদিন লাগে?',            'a' => 'মোবাইল ব্যাংকিং: ১–২ কার্যদিবস। ক্রেডিট/ডেবিট কার্ড: ৫–৭ কার্যদিবস।'],
        ['q' => 'খাবারের মান খারাপ হলে কী করব?',      'a' => 'অ্যাপে ফিরে এসে অর্ডারটি রেট করুন এবং রিভিউতে সমস্যা উল্লেখ করুন। আমাদের টিম দ্রুত ব্যবস্থা নেবে।'],
      ];
    @endphp

    <div id="faqList" style="display:flex; flex-direction:column; gap:10px;">
      @foreach($faqs as $i => $faq)
      <div class="faq-item" style="border:1px solid var(--border); border-radius:var(--radius); overflow:hidden;">
        <button
          onclick="toggleFaq({{ $i }})"
          style="width:100%; text-align:left; padding:14px 16px; background:transparent; border:none; cursor:pointer;
                 display:flex; justify-content:space-between; align-items:center; gap:12px;"
        >
          <span style="font-weight:700; font-size:14px; color:var(--text-1);">{{ $faq['q'] }}</span>
          <i class="fa fa-chevron-down faq-icon faq-icon-{{ $i }}" style="color:var(--text-3); font-size:12px; transition:transform .2s; flex-shrink:0;"></i>
        </button>
        <div class="faq-answer faq-answer-{{ $i }}" style="display:none; padding:0 16px 14px; font-size:13px; color:var(--text-2); line-height:1.65;">
          {{ $faq['a'] }}
        </div>
      </div>
      @endforeach
    </div>
  </div>

</div>

@push('scripts')
<script>
  /* ── Contact card hover ── */
  document.querySelectorAll('.support-contact-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
      card.style.transform = 'translateY(-3px)';
      card.style.boxShadow = '0 8px 24px rgba(0,0,0,.10)';
    });
    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
      card.style.boxShadow = '';
    });
  });

  /* ── Char counter ── */
  const msgEl = document.getElementById('ticketMessage');
  const cntEl = document.getElementById('charCount');
  if (msgEl && cntEl) {
    msgEl.addEventListener('input', () => {
      const len = msgEl.value.length;
      cntEl.textContent = len;
      cntEl.style.color = len > 450 ? 'var(--pink)' : 'var(--text-3)';
      if (len > 500) msgEl.value = msgEl.value.slice(0, 500);
    });
  }

  /* ── FAQ accordion ── */
  function toggleFaq(i) {
    const ans  = document.querySelector('.faq-answer-' + i);
    const icon = document.querySelector('.faq-icon-' + i);
    const open = ans.style.display !== 'none';
    ans.style.display  = open ? 'none' : 'block';
    icon.style.transform = open ? '' : 'rotate(180deg)';
  }

  /* ── Submit ticket ── */
  function submitTicket() {
    const category = document.getElementById('ticketCategory').value;
    const subject  = document.getElementById('ticketSubject').value.trim();
    const message  = document.getElementById('ticketMessage').value.trim();

    if (!category) { showToast('সমস্যার ধরন বেছে নিন!', 'danger'); return; }
    if (!subject)  { showToast('বিষয় লিখুন!', 'danger'); return; }
    if (message.length < 10) { showToast('বিস্তারিত অন্তত ১০ অক্ষর লিখুন!', 'danger'); return; }

    const btn = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> পাঠানো হচ্ছে...';

    fetch('/customer/support/ticket', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        category,
        order_id: document.getElementById('ticketOrderId').value.trim(),
        subject,
        message
      })
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        showToast('✅ টিকেট পাঠানো হয়েছে! শীঘ্রই যোগাযোগ করা হবে।', 'success');
        document.getElementById('ticketCategory').value = '';
        document.getElementById('ticketOrderId').value  = '';
        document.getElementById('ticketSubject').value  = '';
        document.getElementById('ticketMessage').value  = '';
        cntEl.textContent = '০';
        setTimeout(() => location.reload(), 1500);
      } else {
        showToast(d.message || 'পাঠানো যায়নি!', 'danger');
      }
    })
    .catch(() => showToast('নেটওয়ার্ক সমস্যা!', 'danger'))
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-paper-plane"></i> টিকেট পাঠান';
    });
  }

  /* ── Live chat placeholder ── */
  function openLiveChat() {
    showToast('💬 লাইভ চ্যাট শীঘ্রই আসছে!', 'info');
  }
</script>
@endpush