<?php

namespace App\Livewire\Vendor;

use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrderLiveComponent extends Component
{
    public bool $isOnline = true;

    /** এই স্ট্যাটাসগুলোকে "লাইভ" (এখনো চলমান) ধরা হবে */
    private const LIVE_STATUSES = ['pending', 'confirmed', 'preparing', 'picked_up'];

    private const STATUS_META = [
        'pending'   => ['label' => 'নতুন',        'bg' => '#dbeafe', 'color' => '#1e40af'],
        'confirmed' => ['label' => 'রান্না হচ্ছে', 'bg' => '#fef3c7', 'color' => '#92400e'],
        'preparing' => ['label' => 'প্রস্তুত',      'bg' => '#dcfce7', 'color' => '#166534'],
        'picked_up' => ['label' => 'ডেলিভারি',     'bg' => '#fce7f3', 'color' => '#9d174d'],
    ];

    private const BANGLA_DIGITS = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];

    /* ── Computed: এই রেস্তোরাঁর চলমান অর্ডারগুলো, সবচেয়ে পুরোনোটা আগে ── */
    public function getLiveOrdersProperty()
    {
        return Order::with(['items', 'customer'])
            ->where('restaurant_id', $this->restaurantId())
            ->whereIn('status', self::LIVE_STATUSES)
            ->orderBy('created_at')
            ->get()
            ->map(function (Order $order) {
                $meta = self::STATUS_META[$order->status];

                return [
                    'id'           => $order->id,
                    'order_number' => $order->order_number,
                    'customer'     => $order->customer->name ?? 'কাস্টমার',
                    'items'        => $order->items->map(
                        fn ($item) => $item->item_name . ($item->quantity > 1 ? ' × ' . $this->toBanglaNumber($item->quantity) : '')
                    )->implode(', '),
                    'total'        => $order->total_amount_in_taka, // already '৳XXX'
                    'status'       => $order->status,
                    'status_label' => $meta['label'],
                    'status_bg'    => $meta['bg'],
                    'status_color' => $meta['color'],
                    'time_label'   => $this->timeAgoBangla($order->created_at),
                ];
            });
    }

    public function toggleOnline(): void
    {
        $this->isOnline = ! $this->isOnline;
        // TODO: Restaurant::find($this->restaurantId())->update(['is_open' => $this->isOnline]);
    }

    /* ── pending → confirmed ── */
    public function acceptOrder(int $orderId): void
    {
        $this->changeStatus($orderId, 'confirmed', '✅ অর্ডার গ্রহণ করা হয়েছে');
    }

    /* ── pending → cancelled ── */
    public function rejectOrder(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        $from  = $order->status;

        $order->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => 'Rejected by vendor',
        ]);

        $this->logStatus($order, $from, 'cancelled');
        $this->dispatch('show-toast', message: "❌ অর্ডার #{$order->order_number} বাতিল করা হয়েছে", type: 'info');
    }

    /* ── confirmed → preparing ── */
    public function markReady(int $orderId): void
    {
        $this->changeStatus($orderId, 'preparing', '🍳 অর্ডার প্রস্তুত হয়েছে');
    }

    /* ── preparing → picked_up ── */
    public function dispatchOrder(int $orderId): void
    {
        // TODO: এখানে rider_id অ্যাসাইন করার লজিকও যুক্ত করা যেতে পারে
        $this->changeStatus($orderId, 'picked_up', '🛵 অর্ডার ডিসপ্যাচ করা হয়েছে');
    }

    /* ── picked_up → delivered ── */
    public function completeOrder(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        $from  = $order->status;

        $order->update([
            'status'       => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->logStatus($order, $from, 'delivered');
        $this->dispatch('show-toast', message: "🎉 অর্ডার #{$order->order_number} সম্পন্ন হয়েছে", type: 'success');
    }

    private function changeStatus(int $orderId, string $to, string $message): void
    {
        $order = Order::findOrFail($orderId);
        $from  = $order->status;

        $order->update(['status' => $to]);
        $this->logStatus($order, $from, $to);

        $this->dispatch('show-toast', message: "{$message} (#{$order->order_number})", type: 'success');
    }

    private function logStatus(Order $order, string $from, string $to): void
    {
        OrderStatusLog::create([
            'order_id'    => $order->id,
            'from_status' => $from,
            'to_status'   => $to,
            'changed_by'  => Auth::id(),
        ]);
    }

    /** লগইন করা ভেন্ডরের restaurant_id — আপনার User↔Restaurant রিলেশন অনুযায়ী এই লাইনটা ঠিক করুন */
    private function restaurantId(): int
    {
        return Auth::user()->restaurant->id;
    }

    public function toBanglaNumber(int|string $number): string
    {
        return strtr((string) $number, self::BANGLA_DIGITS);
    }

    /** Carbon time → বাংলা "X মিনিট/ঘণ্টা/দিন আগে" */
    private function timeAgoBangla(Carbon $time): string
    {
        $minutes = $time->diffInMinutes(now());

        [$value, $unit] = match (true) {
            $minutes < 60   => [$minutes, 'মিনিট'],
            $minutes < 1440 => [intdiv($minutes, 60), 'ঘণ্টা'],
            default         => [intdiv($minutes, 1440), 'দিন'],
        };

        return "{$this->toBanglaNumber($value)} {$unit} আগে";
    }

    public function render()
    {
        return view('livewire.vendor.order-live-component', [
            'liveOrders' => $this->liveOrders,
        ])->layout('layouts.vendor', [
            'title'           => 'Live Orders | KhaiKhai',
            'breadcrumbTitle' => 'Live Orders',
        ]);
    }
}