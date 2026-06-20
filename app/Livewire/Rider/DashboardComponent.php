<?php

namespace App\Livewire\Rider;

use App\Models\Order;
use App\Models\Review;
use App\Models\RiderEarning;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardComponent extends Component
{
    private const BANGLA_DIGITS = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];

    private const BANGLA_DAYS = [
        0 => 'রবি', 1 => 'সোম', 2 => 'মঙ্গল', 3 => 'বুধ',
        4 => 'বৃহ', 5 => 'শুক্র', 6 => 'শনি',
    ];

    // ── আজকের ডেলিভারি সংখ্যা ──
    public function getTodayDeliveriesProperty(): int
    {
        return Order::where('rider_id', Auth::id())
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->count();
    }

    // ── আজকের আয় (টাকায়) ──
    public function getTodayEarningsProperty(): int
    {
        $total = RiderEarning::where('rider_id', Auth::id())
            ->whereDate('created_at', today())
            ->sum('amount');

        return intdiv((int) $total, 100);
    }

    // ── আমার গড় রেটিং (reviews.delivery_rating) ──
    public function getAvgRatingProperty(): string
    {
        $avg = Review::where('rider_id', Auth::id())
            ->whereNotNull('delivery_rating')
            ->avg('delivery_rating');

        return $avg ? number_format($avg, 1) : '0.0';
    }

    // ── আজকের মোট দূরত্ব (কিমি) — ASSUMPTION: orders.delivery_distance_km ──
    public function getTodayDistanceProperty(): int
    {
        return (int) Order::where('rider_id', Auth::id())
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->sum('id');
            // ->sum('delivery_distance_km');
    }

    // ── চলমান ডেলিভারি (accepted / picked_up) ──
    public function getOngoingOrdersProperty(): array
    {
        return Order::with(['restaurant', 'customer', 'deliveryAddress'])
            ->where('rider_id', Auth::id())
            ->whereIn('status', ['accepted', 'picked_up'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Order $order) {
                $address = optional($order->deliveryAddress);

                return [
                    'id'               => $order->id,
                    'order_number'     => $order->order_number,
                    'restaurant'       => optional($order->restaurant)->name ?? 'রেস্টুরেন্ট',
                    'restaurant_phone' => optional($order->restaurant)->phone,
                    'customer_phone'   => optional($order->customer)->phone,
                    'address'          => $address->address_line ?? $order->delivery_address ?? 'ঠিকানা নেই',
                    'status_label'     => $order->status === 'picked_up' ? 'চলমান' : 'চলমান',
                ];
            })
            ->toArray();
    }

    // ── এই সপ্তাহের প্রতিদিনের আয় (সোম–রবি) ──
    public function getWeeklyEarningsProperty(): array
    {
        $startOfWeek = now()->startOfWeek(Carbon::SATURDAY); // শনি থেকে সপ্তাহ শুরু
        $days        = [];

        for ($i = 0; $i < 7; $i++) {
            $day   = $startOfWeek->copy()->addDays($i);
            $total = RiderEarning::where('rider_id', Auth::id())
                ->whereDate('created_at', $day->toDateString())
                ->sum('amount');

            $days[] = [
                'label'  => self::BANGLA_DAYS[$day->dayOfWeek],
                'amount' => intdiv((int) $total, 100),
            ];
        }

        return $days;
    }

    public function completeDelivery(int $orderId): void
    {
        $order = Order::where('id', $orderId)
            ->where('rider_id', Auth::id())
            ->firstOrFail();

        $order->update([
            'status'       => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->dispatch('order-completed');
    }

    public function toBanglaNumber(int|string $number): string
    {
        return strtr((string) $number, self::BANGLA_DIGITS);
    }

    public function render()
    {
        $weekly      = $this->weeklyEarnings;
        $maxEarning  = max(array_column($weekly, 'amount') ?: [1]);

        return view('livewire.rider.dashboard-component', [
            'todayDeliveries' => $this->toBanglaNumber($this->todayDeliveries),
            'isBestPerformer' => $this->todayDeliveries >= 10,   // ASSUMPTION: ১০+ = সেরা
            'todayEarnings'   => '৳' . $this->toBanglaNumber($this->todayEarnings),
            'avgRating'       => $this->avgRating,
            'todayDistance'   => $this->toBanglaNumber($this->todayDistance),
            'ongoingOrders'   => $this->ongoingOrders,
            'weeklyEarnings'  => $weekly,
            'maxEarning'      => $maxEarning ?: 1,
        ])->layout('layouts.rider', [
            'title'           => 'Dashboard | KhaiKhai',
            'breadcrumbTitle' => 'Dashboard',
        ]);
    }
}