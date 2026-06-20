<div>
    <div class="card-title mb-3">🏷️ অফার ও কুপন</div>

    @php
        $borderColors = ['--pink', '--accent2', '--success', '--accent'];
    @endphp

    <div class="d-flex flex-column gap-3">
        @forelse ($coupons as $i => $coupon)
            @php $color = $borderColors[$i % count($borderColors)]; @endphp

            <div class="card" style="border-left:4px solid var({{ $color }});">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div style="font-size:18px;font-weight:900;color:var({{ $color }});font-family:'Nunito',sans-serif;">
                            {{ $coupon['code'] }}
                        </div>
                        <div style="font-size:13px;color:var(--text-2);margin-top:4px;">
                            {{ $coupon['description'] }} — {{ $coupon['discount_label'] }}
                        </div>
                        @if ($coupon['min_order_taka'])
                            <div style="font-size:11px;color:var(--text-3);margin-top:2px;">
                                <i class="fa fa-bag-shopping"></i> সর্বনিম্ন অর্ডার ৳{{ $coupon['min_order_taka'] }}
                            </div>
                        @endif
                        <div style="font-size:11px;color:var(--text-3);margin-top:4px;">
                            <i class="fa fa-clock"></i>
                            {{ $coupon['valid_until'] ? 'মেয়াদ: ' . $coupon['valid_until'] : 'মেয়াদ নির্দিষ্ট নেই' }}
                        </div>
                    </div>
                    <button
                        class="btn-kk btn-outline-kk btn-sm-kk"
                        onclick="navigator.clipboard.writeText('{{ $coupon['code'] }}'); showToast('কুপন {{ $coupon['code'] }} কপি হয়েছে! 📋','success')"
                    >
                        <i class="fa fa-copy"></i> কপি
                    </button>
                </div>
            </div>
        @empty
            <div class="card text-center py-4" style="color:var(--text-3);">
                😔 এই মুহূর্তে কোনো অফার নেই, পরে আবার চেক করুন।
            </div>
        @endforelse
    </div>
</div>