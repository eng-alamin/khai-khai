<div>

    {{-- HEADER --}}
    <div class="card mb-4" style="padding:16px 20px;">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span>
                <span class="card-title mb-0" style="font-size:18px;">লাইভ অর্ডার</span>
                <span class="badge-kk" style="background:#fce7f3;color:var(--pink);font-weight:800;">
                    {{ $this->toBanglaNumber(count($liveOrders)) }}টি
                </span>
            </div>

            <button
                type="button"
                wire:click="toggleOnline"
                class="d-flex align-items-center gap-2"
                style="background:none;border:none;cursor:pointer;padding:0;"
            >
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $isOnline ? 'var(--success)' : '#9ca3af' }};display:inline-block;"></span>
                <span style="font-size:13px;color:var(--text-2);font-weight:600;">{{ $isOnline ? 'অনলাইন' : 'অফলাইন' }}</span>
            </button>
        </div>
    </div>

    {{-- LIVE ORDER LIST --}}
    <div class="d-flex flex-column gap-3">
        @forelse($liveOrders as $order)
            <div class="card" wire:key="live-order-{{ $order['id'] }}" style="padding:18px 20px;">

                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div style="font-weight:800;font-size:16px;color:var(--pink);">
                        #{{ $order['order_number'] }} — {{ $order['customer'] }}
                    </div>
                    <span class="badge-kk" style="background:{{ $order['status_bg'] }};color:{{ $order['status_color'] }};font-weight:700;">
                        {{ $order['status_label'] }}
                    </span>
                </div>

                <div class="d-flex gap-2 mb-3" style="font-size:13px;color:var(--text-3);">
                    <span><i class="fa fa-clock me-1"></i>{{ $order['time_label'] }}</span>
                    <span>•</span>
                    <span>{{ $order['items'] }}</span>
                </div>

                <div class="d-flex align-items-center justify-content-between">
                    <div style="font-size:22px;font-weight:900;">{{ $order['total'] }}</div>

                    <div class="d-flex gap-2">
                        @if($order['status'] === 'pending')
                            <button
                                class="btn-kk btn-sm-kk"
                                style="background:var(--success);color:#fff;"
                                wire:click="acceptOrder({{ $order['id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="acceptOrder({{ $order['id'] }}), rejectOrder({{ $order['id'] }})"
                            ><i class="fa fa-check"></i> গ্রহণ</button>

                            <button
                                class="btn-kk btn-sm-kk"
                                style="background:#fee2e2;color:#991b1b;"
                                wire:click="rejectOrder({{ $order['id'] }})"
                                wire:confirm="আপনি কি নিশ্চিত এই অর্ডার বাতিল করতে চান?"
                                wire:loading.attr="disabled"
                                wire:target="acceptOrder({{ $order['id'] }}), rejectOrder({{ $order['id'] }})"
                            ><i class="fa fa-times"></i> বাতিল</button>

                        @elseif($order['status'] === 'confirmed')
                            <button
                                class="btn-kk btn-sm-kk"
                                style="background:var(--warning);color:#fff;"
                                wire:click="markReady({{ $order['id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="markReady({{ $order['id'] }})"
                            ><i class="fa fa-utensils"></i> প্রস্তুত</button>

                        @elseif($order['status'] === 'preparing')
                            <button
                                class="btn-kk btn-sm-kk"
                                style="background:var(--pink);color:#fff;"
                                wire:click="dispatchOrder({{ $order['id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="dispatchOrder({{ $order['id'] }})"
                            ><i class="fa fa-motorcycle"></i> ডিসপ্যাচ</button>

                        @elseif($order['status'] === 'picked_up')
                            <button
                                class="btn-kk btn-outline-kk btn-sm-kk"
                                wire:click="completeOrder({{ $order['id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="completeOrder({{ $order['id'] }})"
                            ><i class="fa fa-check"></i> সম্পন্ন</button>
                        @endif
                    </div>
                </div>

            </div>
        @empty
            <div class="card text-center py-5">
                <div style="font-size:64px;" class="mb-3">🎉</div>
                <div class="fw-bold fs-5 mb-2">এখন কোনো লাইভ অর্ডার নেই</div>
                <div class="text-muted small">নতুন অর্ডার আসলে এখানে স্বয়ংক্রিয়ভাবে দেখা যাবে।</div>
            </div>
        @endforelse
    </div>

</div>

@push('styles')
    <style>
        :root {
            --pink: #ec4899;
            --accent: #6366f1;
            --accent2: #f59e0b;
            --success: #16a34a;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-1: #1f2937;
            --text-2: #4b5563;
            --text-3: #9ca3af;
            --border: #e5e7eb;
            --radius: 14px;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }
        .card { background:#fff; border-radius:var(--radius); box-shadow:var(--shadow); border:1px solid var(--border); padding:18px 20px; width:100%; }
        .badge-kk { display:inline-flex; align-items:center; padding:5px 12px; border-radius:999px; font-size:12px; font-weight:700; white-space:nowrap; }
        .btn-kk { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:9px 16px; border-radius:10px; font-size:13px; font-weight:700; border:none; cursor:pointer; white-space:nowrap; transition:opacity .15s, transform .1s; text-decoration:none; }
        .btn-kk:hover { opacity:0.88; }
        .btn-kk:active { transform:scale(0.97); }
        .btn-kk:disabled { opacity:0.5; cursor:not-allowed; }
        .btn-sm-kk { padding:7px 13px; font-size:12px; }
        .btn-primary-kk { background:var(--pink); color:#fff; }
        .btn-outline-kk { background:#fff; color:var(--text-2); border:1.5px solid var(--border); }
        .btn-ghost-kk { background:#f3f4f6; color:var(--text-2); }
        .d-flex { display:flex; }
        .flex-column { flex-direction:column; }
        .align-items-center { align-items:center; }
        .align-items-start { align-items:flex-start; }
        .justify-content-between { justify-content:space-between; }
        .gap-1 { gap:4px; } .gap-2 { gap:8px; } .gap-3 { gap:12px; }
        .mb-1 { margin-bottom:4px; } .mb-2 { margin-bottom:8px; } .mb-3 { margin-bottom:12px; } .mb-4 { margin-bottom:16px; }
    </style>
@endpush