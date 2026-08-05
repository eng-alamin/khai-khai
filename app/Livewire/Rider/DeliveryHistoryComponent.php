<?php

namespace App\Livewire\Rider;

use App\Models\Order;
use App\Models\RiderEarning;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveryHistoryComponent extends Component
{
    use WithPagination;

    /** Number of rows to show per page */
    public int $perPage = 10;

    private const MONTH_LABELS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar',  4 => 'Apr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul',  8 => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    /* ── Computed: total completed deliveries for this rider (top badge) ── */
    public function getTotalDeliveredProperty(): int
    {
        return Order::where('rider_id', Auth::id())
            ->where('status', 'delivered')
            ->count();
    }

    /* ── Computed: Paginated delivery history ── */
    public function getHistoryOrdersProperty()
    {
        return Order::with(['customer', 'restaurant'])
            ->where('rider_id', Auth::id())
            ->where('status', 'delivered')
            ->orderByDesc('delivered_at')
            ->paginate($this->perPage);
    }

    /* ── Helper: batch-fetch earnings & ratings for all orders on this page (avoids N+1) ── */
    private function earningsAndRatingsFor(array $orderIds): array
    {
        $earnings = RiderEarning::whereIn('order_id', $orderIds)
            ->pluck('amount', 'order_id');

        $ratings = \App\Models\Review::whereIn('order_id', $orderIds)
            ->whereNotNull('delivery_rating')
            ->pluck('delivery_rating', 'order_id');

        return [$earnings, $ratings];
    }

    /* ── Helper: date label — Today / Yesterday / X days ago / full date ── */
    private function dateLabel(?Carbon $time): string
    {
        if (! $time) {
            return '-';
        }

        if ($time->isToday()) {
            return 'Today ' . $time->format('g:i A');
        }

        if ($time->isYesterday()) {
            return 'Yesterday';
        }

        $days = (int) $time->diffInDays(now());

        if ($days < 7) {
            return $days . ' days ago';
        }

        return $time->day . ' ' . self::MONTH_LABELS[$time->month] . ', ' . $time->year;
    }

    public function render()
    {
        // Convert each order into a UI-friendly array (keeping pagination meta intact)
        $orderIds = $this->historyOrders->pluck('id')->all();
        [$earnings, $ratings] = $this->earningsAndRatingsFor($orderIds);

        $rows = $this->historyOrders->through(function (Order $order) use ($earnings, $ratings) {
            $earning = $earnings->get($order->id);

            return [
                'id'           => $order->id,
                'order_number' => $order->order_number,
                'customer'     => $order->customer->name ?? 'Customer',
                'restaurant'   => $order->restaurant->name ?? 'Restaurant',
                'date_label'   => $this->dateLabel($order->delivered_at ?? $order->updated_at),
                'earning'      => $earning !== null ? 'Tk ' . $earning : '-',
                'rating'       => $ratings->get($order->id),
            ];
        });

        return view('livewire.rider.delivery-history-component', [
            'rows'           => $rows,
            'totalDelivered' => $this->totalDelivered,
        ])->layout('layouts.rider', [
            'title'           => 'Delivery History | KhaiKhai',
            'breadcrumbTitle' => 'Delivery History',
        ]);
    }
}