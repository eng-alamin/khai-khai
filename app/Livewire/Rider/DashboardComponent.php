<?php

namespace App\Livewire\Rider;

use App\Models\Order;
use App\Models\Review;
use App\Models\RiderEarning;
use App\Services\RiderDeliveryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardComponent extends Component
{
    private const DAY_LABELS = [
        0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed',
        4 => 'Thu', 5 => 'Fri', 6 => 'Sat',
    ];

    // ── Today's delivery count ──
    public function getTodayDeliveriesProperty(): int
    {
        return Order::where('rider_id', Auth::id())
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->count();
    }

    // ── Today's earnings (in currency) ──
    public function getTodayEarningsProperty(): int
    {
        $total = RiderEarning::where('rider_id', Auth::id())
            ->whereDate('created_at', today())
            ->sum('amount');

        return $total;
    }

    // ── My average rating (reviews.delivery_rating) ──
    public function getAvgRatingProperty(): string
    {
        $avg = Review::where('rider_id', Auth::id())
            ->whereNotNull('delivery_rating')
            ->avg('delivery_rating');

        return $avg ? number_format($avg, 1) : '0.0';
    }

    // ── Today's total distance (km) ──
    public function getTodayDistanceProperty(): int
    {
        return (int) round(Order::where('rider_id', Auth::id())
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->sum('delivery_distance_km'));
    }

    // ── Ongoing deliveries (orders the rider has picked up and is delivering) ──
    public function getOngoingOrdersProperty(): array
    {
        return Order::with(['restaurant', 'customer', 'deliveryAddress'])
            ->where('rider_id', Auth::id())
            ->where('status', 'picked_up')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Order $order) {
                $address = $order->deliveryAddress;
                $snapshot = $order->delivery_address_snapshot;

                return [
                    'id'               => $order->id,
                    'order_number'     => $order->order_number,
                    'restaurant'       => $order->isAdminOrder() ? 'KhaiKhai Store' : (optional($order->restaurant)->name ?? 'Restaurant'),
                    'restaurant_phone' => optional($order->restaurant)->phone,
                    'customer_phone'   => optional($order->customer)->phone,
                    'address'          => $address->full_address
                        ?? ($snapshot['full_address'] ?? null)
                        ?? 'No address',
                    'status_label'     => 'Ongoing',
                ];
            })
            ->toArray();
    }

    // ── This week's daily earnings (Sat–Fri) ──
    public function getWeeklyEarningsProperty(): array
    {
        $startOfWeek = now()->startOfWeek(Carbon::SATURDAY); // Week starts on Saturday
        $days        = [];

        for ($i = 0; $i < 7; $i++) {
            $day   = $startOfWeek->copy()->addDays($i);
            $total = RiderEarning::where('rider_id', Auth::id())
                ->whereDate('created_at', $day->toDateString())
                ->sum('amount');

            $days[] = [
                'label'  => self::DAY_LABELS[$day->dayOfWeek],
                'amount' => $total,
            ];
        }

        return $days;
    }

    public function completeDelivery(int $orderId): void
    {
        app(RiderDeliveryService::class)->completeDelivery($orderId, Auth::user());

        $this->dispatch('order-completed');
    }

    public function render()
    {
        $weekly      = $this->weeklyEarnings;
        $maxEarning  = max(array_column($weekly, 'amount') ?: [1]);

        return view('livewire.rider.dashboard-component', [
            'todayDeliveries' => $this->todayDeliveries,
            'isBestPerformer' => $this->todayDeliveries >= 10,   // ASSUMPTION: 10+ = best performer
            'todayEarnings'   => 'Tk ' . number_format($this->todayEarnings),
            'avgRating'       => $this->avgRating,
            'todayDistance'   => $this->todayDistance,
            'ongoingOrders'   => $this->ongoingOrders,
            'weeklyEarnings'  => $weekly,
            'maxEarning'      => $maxEarning ?: 1,
        ])->layout('layouts.rider', [
            'title'           => 'Dashboard | KhaiKhai',
            'breadcrumbTitle' => 'Dashboard',
        ]);
    }
}