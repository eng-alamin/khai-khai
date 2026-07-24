<?php

namespace App\Livewire\Rider;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\RiderProfile;
use App\Models\RiderEarning;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeliveryOngoingComponent extends Component
{
    public bool $isOnline = true;
    // public bool $isOnline = false;

    /** রাইডারের চলমান ডেলিভারি স্ট্যাটাস */
    private const ONGOING_STATUSES = ['picked_up'];

    /** রাইডার যে অর্ডারগুলো অ্যাসাইন পেতে পারে (vendor dispatch করেছে, rider নেয়নি) */
    private const AVAILABLE_STATUSES = ['preparing'];

    private const STATUS_META = [
        'preparing' => ['label' => 'পিকআপ পেন্ডিং', 'bg' => '#fef3c7', 'color' => '#92400e'],
        'picked_up' => ['label' => 'ডেলিভারিতে',     'bg' => '#dbeafe', 'color' => '#1e40af'],
        'delivered' => ['label' => 'সম্পন্ন',          'bg' => '#dcfce7', 'color' => '#166534'],
    ];

    private const BANGLA_DIGITS = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];

    /* ── Computed: রাইডারের বর্তমান চলমান ডেলিভারি (picked_up) ── */
    public function getOngoingOrdersProperty()
    {
        return Order::with(['items', 'customer', 'restaurant'])
            ->where('rider_id', Auth::id())
            ->whereIn('status', self::ONGOING_STATUSES)
            ->orderBy('updated_at')
            ->get()
            ->map(fn (Order $order) => $this->formatOrder($order));
    }

    /* ── Computed: এলাকায় পিকআপ-যোগ্য অর্ডার (rider assign হয়নি, preparing) ── */
    public function getAvailableOrdersProperty()
    {
        if (! $this->isOnline) {
            return collect();
        }

        return Order::with(['items', 'customer', 'restaurant'])
            ->whereNull('rider_id')
            ->whereIn('status', self::AVAILABLE_STATUSES)
            ->orderBy('created_at')
            ->get()
            ->map(fn (Order $order) => $this->formatOrder($order));
    }

    /* ── অনলাইন/অফলাইন টগল ── */
    public function toggleOnline(): void
    {
        $this->isOnline = ! $this->isOnline;

        RiderProfile::where('user_id', Auth::id())->update(['is_online' => $this->isOnline]);

        $status = $this->isOnline ? 'অনলাইন' : 'অফলাইন';
        $this->dispatch('show-toast', message: "আপনি এখন {$status}", type: 'info');
    }

    /* ── রাইডার অর্ডার অ্যাকসেপ্ট করে পিকআপে যাবে ── */
    public function acceptDelivery(int $orderId): void
    {
        $order = Order::whereNull('rider_id')
            ->where('status', 'preparing')
            ->findOrFail($orderId);

        $from = $order->status;

        $order->update([
            'rider_id' => Auth::id(),
            'status'   => 'picked_up',
        ]);

        $this->logStatus($order, $from, 'picked_up');
        $this->dispatch('show-toast', message: "✅ অর্ডার #{$order->order_number} আপনার কাছে অ্যাসাইন হয়েছে", type: 'success');
    }

    /* ── ডেলিভারি সম্পন্ন ── */
    public function completeDelivery(int $orderId): void
    {
        $order = Order::where('rider_id', Auth::id())
            ->where('status', 'picked_up')
            ->findOrFail($orderId);

        $from = $order->status;

        $order->update([
            'status'       => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->logStatus($order, $from, 'delivered');

        // Rider earning রেকর্ড
        RiderEarning::firstOrCreate(
            ['order_id' => $order->id],
            [
                'rider_id'      => Auth::id(),
                'amount'        => $order->delivery_fee,   // delivery_fee থেকে earning (BDT paisa)
                'payout_status' => 'pending',
            ]
        );

        $this->dispatch('show-toast', message: "🎉 অর্ডার #{$order->order_number} ডেলিভারি সম্পন্ন!", type: 'success');
    }

    /* ── আজকের আয়ের summary ── */
    public function getTodayEarningsProperty(): array
    {
        $earnings = RiderEarning::where('rider_id', Auth::id())
            ->whereDate('created_at', today())
            ->selectRaw('COUNT(*) as deliveries, SUM(amount) as total_paisa')
            ->first();

        $totalPaisa   = (int) ($earnings->total_paisa ?? 0);
        $deliveries   = (int) ($earnings->deliveries ?? 0);

        return [
            'deliveries' => $deliveries,
            'total_taka' => '৳' . $this->toBanglaNumber(intdiv($totalPaisa, 100)),
        ];
    }

    /* ── Helper: Order → array ── */
    private function formatOrder(Order $order): array
    {
        $meta = self::STATUS_META[$order->status] ?? ['label' => $order->status, 'bg' => '#f3f4f6', 'color' => '#374151'];

        $snapshot = is_array($order->delivery_address_snapshot)
            ? $order->delivery_address_snapshot
            : json_decode($order->delivery_address_snapshot ?? '{}', true);

        $address = trim(
            ($snapshot['address_line'] ?? '') . ', ' . ($snapshot['area'] ?? '') . ', ' . ($snapshot['city'] ?? '')
        , ', ');

        return [
            'id'              => $order->id,
            'order_number'    => $order->order_number,
            'customer'        => $order->customer->name ?? 'কাস্টমার',
            'customer_phone'  => $order->customer->phone ?? '',
            'restaurant'      => $order->restaurant->name ?? 'রেস্টুরেন্ট',
            'restaurant_phone'=> $order->restaurant->phone ?? '',
            'items'           => $order->items->map(
                fn ($item) => ($item->emoji ? $item->emoji . ' ' : '') . $item->item_name .
                    ($item->quantity > 1 ? ' × ' . $this->toBanglaNumber($item->quantity) : '')
            )->implode(', '),
            'item_count'      => $order->items->sum('quantity'),
            'total'           => '৳' . $order->total_amount,
            'delivery_fee'    => '৳' . $order->delivery_fee,
            'address'         => $address ?: 'ঠিকানা পাওয়া যায়নি',
            'payment_method'  => $order->payment_method,
            'payment_status'  => $order->payment_status,
            'status'          => $order->status,
            'status_label'    => $meta['label'],
            'status_bg'       => $meta['bg'],
            'status_color'    => $meta['color'],
            'time_label'      => $this->timeAgoBangla($order->created_at),
        ];
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

    public function toBanglaNumber(int|string $number): string
    {
        return strtr((string) $number, self::BANGLA_DIGITS);
    }

    private function timeAgoBangla(Carbon $time): string
    {
        $minutes = $time->diffInMinutes(now());

        [$value, $unit] = match (true) {
            $minutes < 60   => [$minutes, 'মিনিট'],
            $minutes < 1440 => [intdiv($minutes, 60), 'ঘণ্টা'],
            default         => [intdiv($minutes, 1440), 'দিন'],
        };

        return $this->toBanglaNumber($value) . ' ' . $unit . ' আগে';
    }

    public function render()
    {
        return view('livewire.rider.delivery-ongoing-component', [
            'ongoingOrders'   => $this->ongoingOrders,
            'availableOrders' => $this->availableOrders,
            'todayEarnings'   => $this->todayEarnings,
        ])->layout('layouts.rider', [
            'title'           => 'Ongoing Delivery | KhaiKhai',
            'breadcrumbTitle' => 'Ongoing Delivery',
        ]);
    }
}