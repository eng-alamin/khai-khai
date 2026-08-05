<?php

namespace App\Livewire\Rider;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\RiderProfile;
use App\Models\RiderEarning;
use App\Services\RiderDeliveryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeliveryOngoingComponent extends Component
{
    public bool $isOnline = false;

    /** Rider's currently active delivery statuses */
    private const ONGOING_STATUSES = ['picked_up'];

    /** Orders the rider can be assigned (vendor dispatched, no rider taken it yet) */
    private const AVAILABLE_STATUSES = ['preparing'];

    private const STATUS_META = [
        'preparing' => ['label' => 'Pickup Pending', 'bg' => '#fef3c7', 'color' => '#92400e'],
        'picked_up' => ['label' => 'Out for Delivery', 'bg' => '#dbeafe', 'color' => '#1e40af'],
        'delivered' => ['label' => 'Completed',        'bg' => '#dcfce7', 'color' => '#166534'],
    ];

    /**
     * BUG FIX: $isOnline used to be hardcoded to `true` with no way to know
     * the rider's real saved status, so a page refresh would silently
     * override whatever was actually stored in rider_profiles.is_online
     * (set via toggleOnline()). We now hydrate it from the DB on mount so
     * the UI always reflects the rider's real online/offline state.
     */
    public function mount(): void
    {
        $this->isOnline = (bool) RiderProfile::where('user_id', Auth::id())->value('is_online');
    }

    /* ── Computed: rider's current ongoing deliveries (picked_up) ── */
    public function getOngoingOrdersProperty()
    {
        return Order::with(['items', 'customer', 'restaurant'])
            ->where('rider_id', Auth::id())
            ->whereIn('status', self::ONGOING_STATUSES)
            ->orderBy('updated_at')
            ->get()
            ->map(fn (Order $order) => $this->formatOrder($order));
    }

    /* ── Computed: nearby orders available for pickup (unassigned, preparing) ── */
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

    /* ── Toggle online/offline ── */
    public function toggleOnline(): void
    {
        $this->isOnline = ! $this->isOnline;

        RiderProfile::where('user_id', Auth::id())->update(['is_online' => $this->isOnline]);

        $status = $this->isOnline ? 'online' : 'offline';
        $this->dispatch('show-toast', message: "You are now {$status}", type: 'info');
    }

    /* ── Rider accepts the order and heads to pickup ── */
    public function acceptDelivery(int $orderId): void
    {
        // Atomic conditional update — avoids the race condition where two
        // riders could accept the same order at the same moment.
        $affected = Order::where('id', $orderId)
            ->whereNull('rider_id')
            ->where('status', 'preparing')
            ->update([
                'rider_id' => Auth::id(),
                'status'   => 'picked_up',
            ]);

        if ($affected === 0) {
            $this->dispatch('show-toast', message: '⚠️ Sorry, this order has already been taken by another rider.', type: 'error');
            return;
        }

        $order = Order::findOrFail($orderId);
        $this->logStatus($order, 'preparing', 'picked_up');

        activity()
            ->causedBy(Auth::user())
            ->performedOn($order)
            ->log('Rider accepted delivery');

        $this->dispatch('show-toast', message: "✅ Order #{$order->order_number} has been assigned to you", type: 'success');
    }

    /* ── Complete delivery ── */
    public function completeDelivery(int $orderId): void
    {
        $order = app(RiderDeliveryService::class)->completeDelivery($orderId, Auth::user());

        $this->dispatch('show-toast', message: "🎉 Order #{$order->order_number} delivered!", type: 'success');
    }

    /* ── Today's earnings summary ── */
    public function getTodayEarningsProperty(): array
    {
        $earnings = RiderEarning::where('rider_id', Auth::id())
            ->whereDate('created_at', today())
            ->selectRaw('COUNT(*) as deliveries, SUM(amount) as total_amount')
            ->first();

        // NOTE: rider_earnings.amount is stored directly in Taka (it's a
        // straight copy of orders.delivery_fee, which DeliveryChargeService
        // explicitly computes/returns as "Taka, decimal") — it is NOT paisa,
        // so no /100 conversion belongs here.
        $totalAmount = (int) round($earnings->total_amount ?? 0);
        $deliveries  = (int) ($earnings->deliveries ?? 0);

        return [
            'deliveries' => $deliveries,
            'total_taka' => 'Tk ' . number_format($totalAmount),
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
            ($snapshot['full_address'] ?? '') . ', ' . ($snapshot['city'] ?? '')
        , ', ');

        return [
            'id'              => $order->id,
            'order_number'    => $order->order_number,
            'customer'        => $order->customer->name ?? 'Customer',
            'customer_phone'  => $order->customer->phone ?? '',
            'restaurant'      => $order->restaurant->name ?? 'Restaurant',
            'restaurant_phone'=> $order->restaurant->phone ?? '',
            'items'           => $order->items->map(
                fn ($item) => ($item->emoji ? $item->emoji . ' ' : '') . $item->item_name .
                    ($item->quantity > 1 ? ' × ' . $item->quantity : '')
            )->implode(', '),
            'item_count'      => $order->items->sum('quantity'),
            'total'           => 'Tk ' . $order->total_amount,
            'delivery_fee'    => 'Tk ' . $order->delivery_fee,
            'address'         => $address ?: 'Address not available',
            'payment_method'  => $order->payment_method,
            'payment_status'  => $order->payment_status,
            'status'          => $order->status,
            'status_label'    => $meta['label'],
            'status_bg'       => $meta['bg'],
            'status_color'    => $meta['color'],
            'time_label'      => $this->timeAgo($order->created_at),
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

    private function timeAgo(Carbon $time): string
    {
        $minutes = $time->diffInMinutes(now());

        [$value, $unit] = match (true) {
            $minutes < 60   => [$minutes, 'min'],
            $minutes < 1440 => [intdiv($minutes, 60), 'hr'],
            default         => [intdiv($minutes, 1440), 'day'],
        };

        $unitLabel = $value === 1 ? $unit : $unit . 's';

        return $value . ' ' . $unitLabel . ' ago';
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