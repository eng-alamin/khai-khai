<?php

namespace App\Livewire\Rider;

use App\Models\Order;
use App\Models\RiderProfile;
use App\Models\RiderEarning;
use App\Services\OrderNotifier;
use App\Services\OrderTransitionService;
use App\Services\RiderDeliveryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class DeliveryOngoingComponent extends Component
{
    /** Locked: only the server may change it (set from the database / toggleOnline). */
    #[Locked]
    public bool $isOnline = false;

    /**
     * Statuses of the rider's own active orders:
     *  - ready     + rider_id = me  -> accepted, still going to the restaurant
     *  - picked_up + rider_id = me  -> food collected, delivering to the customer
     */
    private const ONGOING_STATUSES = ['ready', 'picked_up'];

    /** Soft limit so one rider cannot hoard orders other riders could take. */
    private const MAX_ACTIVE_ORDERS = 3;

    /** Orders the rider can take (vendor marked them ready, no rider has taken them yet) */
    private const AVAILABLE_STATUSES = ['ready'];

    private const STATUS_META = [
        'ready'     => ['label' => 'Pickup Pending', 'bg' => '#fef3c7', 'color' => '#92400e'],
        'assigned'  => ['label' => 'Go to Restaurant', 'bg' => '#ede9fe', 'color' => '#5b21b6'],
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

    /* ── Computed: rider's own active orders (accepted or picked up) ── */
    public function getOngoingOrdersProperty()
    {
        return Order::with(['items', 'customer', 'restaurant'])
            ->where('rider_id', Auth::id())
            ->whereIn('status', self::ONGOING_STATUSES)
            ->orderBy('updated_at')
            ->get()
            ->map(fn (Order $order) => $this->formatOrder($order));
    }

    /* ── Computed: nearby orders available for pickup (unassigned, ready for pickup) ── */
    public function getAvailableOrdersProperty()
    {
        // Customer name, phone and address must never reach an unapproved or offline rider.
        if (! $this->isOnline || ! Auth::user()->canDeliver()) {
            return collect();
        }

        // No 'customer' relation here on purpose: unclaimed orders must never
        // carry the customer's personal data.
        return Order::with(['items', 'restaurant'])
            ->whereNull('rider_id')
            ->whereIn('status', self::AVAILABLE_STATUSES)
            ->orderBy('created_at')
            ->get()
            ->map(fn (Order $order) => $this->formatOrder($order));
    }

    /* ── Toggle online/offline ── */
    public function toggleOnline(): void
    {
        $user = Auth::user();

        if (! $user->canDeliver()) {
            $this->dispatch('show-toast', message: 'আপনার অ্যাকাউন্ট এখনো অনুমোদিত হয়নি।', type: 'warning');
            return;
        }

        // Flip the value stored in the database, not the one held in the page.
        $profile = $user->riderProfile;
        $profile->update(['is_online' => ! $profile->is_online]);
        $this->isOnline = (bool) $profile->is_online;

        $status = $this->isOnline ? 'online' : 'offline';
        $this->dispatch('show-toast', message: "You are now {$status}", type: 'info');
    }

    /* ── Rider accepts the order (status stays "ready" until the food is collected) ── */
    public function acceptDelivery(int $orderId): void
    {
        $user = Auth::user();

        if (! $user->canDeliver() || ! $user->riderProfile?->is_online) {
            $this->dispatch('show-toast', message: 'অর্ডার নিতে আপনাকে অনুমোদিত ও অনলাইন থাকতে হবে।', type: 'error');
            return;
        }

        $activeCount = Order::where('rider_id', $user->id)
            ->whereIn('status', self::ONGOING_STATUSES)
            ->count();

        if ($activeCount >= self::MAX_ACTIVE_ORDERS) {
            $this->dispatch(
                'show-toast',
                message: 'একসাথে সর্বোচ্চ ' . self::MAX_ACTIVE_ORDERS . 'টি অর্ডার নেওয়া যাবে। আগে একটি শেষ করুন।',
                type: 'warning'
            );
            return;
        }

        // Atomic conditional update — avoids the race condition where two
        // riders could accept the same order at the same moment.
        $affected = Order::where('id', $orderId)
            ->whereNull('rider_id')
            ->where('status', 'ready')
            ->update([
                'rider_id'    => $user->id,
                'assigned_at' => now(),
                'accepted_at' => now(),
            ]);

        if ($affected === 0) {
            $this->dispatch('show-toast', message: '⚠️ Sorry, this order has already been taken by another rider.', type: 'error');
            return;
        }

        $order = Order::findOrFail($orderId);

        activity()
            ->causedBy($user)
            ->performedOn($order)
            ->log('Rider accepted delivery');

        app(OrderNotifier::class)->riderAccepted($order);

        $this->dispatch('show-toast', message: "✅ Order #{$order->order_number} is yours. Go to the restaurant to pick it up.", type: 'success');
    }

    /* ── Rider collected the food from the restaurant ── */
    public function pickupOrder(int $orderId): void
    {
        $user = Auth::user();

        if (! $user->canDeliver()) {
            $this->dispatch('show-toast', message: 'আপনার অ্যাকাউন্ট এখনো অনুমোদিত হয়নি।', type: 'error');
            return;
        }

        // Status change + status log are written together inside the service.
        if (! app(OrderTransitionService::class)->pickUp($orderId, $user)) {
            $this->dispatch('show-toast', message: 'This order can no longer be marked as picked up.', type: 'error');
            return;
        }

        $order = Order::findOrFail($orderId);

        $this->dispatch('show-toast', message: "🛵 Order #{$order->order_number} picked up. Deliver it to the customer.", type: 'success');
    }

    /* ── Rider gives the order back (only before the food is collected) ── */
    public function releaseDelivery(int $orderId): void
    {
        $user = Auth::user();

        $affected = Order::where('id', $orderId)
            ->where('rider_id', $user->id)
            ->where('status', 'ready')
            ->update([
                'rider_id'    => null,
                'assigned_at' => null,
                'accepted_at' => null,
            ]);

        if ($affected === 0) {
            $this->dispatch('show-toast', message: 'This order can no longer be released.', type: 'error');
            return;
        }

        $order = Order::findOrFail($orderId);

        activity()
            ->causedBy($user)
            ->performedOn($order)
            ->log('Rider released order');

        app(OrderNotifier::class)->riderReleased($order, $user->id);

        $this->dispatch('show-toast', message: "Order #{$order->order_number} was released for other riders.", type: 'info');
    }

    /* ── Rider cannot hand over a picked-up order (customer not answering, wrong address, ...) ── */
    public function reportDeliveryIssue(int $orderId, string $reason, string $note = ''): void
    {
        // The reason KEY is checked against a fixed list; the text saved in
        // the database is chosen by the server, never taken from the page.
        if (! array_key_exists($reason, OrderTransitionService::DELIVERY_ISSUES)) {
            $this->dispatch('show-toast', message: 'একটি কারণ বেছে নিন।', type: 'warning');
            return;
        }

        try {
            $reported = app(OrderTransitionService::class)->reportDeliveryIssue(
                $orderId,
                Auth::user(),
                $reason,
                $note
            );
        } catch (ModelNotFoundException) {
            $this->dispatch('show-toast', message: 'This order is not available.', type: 'warning');
            return;
        }

        if (! $reported) {
            $this->dispatch('show-toast', message: 'সমস্যাটি আগেই জানানো হয়েছে, অথবা অর্ডারটি আর picked up নেই।', type: 'info');
            return;
        }

        $this->dispatch('show-toast', message: 'অ্যাডমিনকে জানানো হয়েছে। সিদ্ধান্তের অপেক্ষা করুন।', type: 'success');
    }

    /* ── Complete delivery ── */
    public function completeDelivery(int $orderId): void
    {
        try {
            $order = app(RiderDeliveryService::class)->completeDelivery($orderId, Auth::user());
        } catch (ModelNotFoundException) {
            // Double click, or the order is no longer "picked_up" for this rider.
            $this->dispatch('show-toast', message: 'This delivery was already completed or is not available.', type: 'warning');
            return;
        }

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
        // "ready" + a rider = accepted, rider still has to collect the food.
        $stage = match (true) {
            $order->status === 'picked_up'                              => 'delivering',
            $order->status === 'ready' && $order->rider_id !== null     => 'to_pickup',
            default                                                     => 'available',
        };

        $metaKey = $stage === 'to_pickup' ? 'assigned' : $order->status;
        $meta    = self::STATUS_META[$metaKey] ?? ['label' => $order->status, 'bg' => '#f3f4f6', 'color' => '#374151'];

        $snapshot = is_array($order->delivery_address_snapshot)
            ? $order->delivery_address_snapshot
            : json_decode($order->delivery_address_snapshot ?? '{}', true);

        // Before a rider accepts the order, only a rough area + distance is
        // shown. Customer name, phone and street address stay private until
        // this rider owns the order.
        $isOwned = $stage !== 'available';

        if ($isOwned) {
            $address = trim(
                ($snapshot['full_address'] ?? '') . ', ' . ($snapshot['city'] ?? '')
            , ', ');
            $address = $address !== '' ? $address : 'Address not available';
        } else {
            $area     = trim((string) ($snapshot['city'] ?? ''));
            $distance = $order->delivery_distance_km !== null
                ? number_format((float) $order->delivery_distance_km, 1) . ' km'
                : '';

            $summary = implode(' · ', array_filter([$area, $distance]));
            $address = $summary !== '' ? $summary . ' (full address after accepting)' : 'Full address after accepting';
        }

        return [
            'id'              => $order->id,
            'order_number'    => $order->order_number,
            'customer'        => $isOwned ? ($order->customer->name ?? 'Customer') : 'Customer',
            'customer_phone'  => $isOwned ? ($order->customer->phone ?? '') : '',
            'restaurant'      => $order->isAdminOrder() ? 'KhaiKhai Store' : ($order->restaurant->name ?? 'Restaurant'),
            'restaurant_phone'=> $order->restaurant->phone ?? '',
            'items'           => $order->items->map(
                fn ($item) => ($item->emoji ? $item->emoji . ' ' : '') . $item->item_name .
                    ($item->quantity > 1 ? ' × ' . $item->quantity : '')
            )->implode(', '),
            'item_count'      => $order->items->sum('quantity'),
            'total'           => 'Tk ' . $order->total_amount,
            'delivery_fee'    => 'Tk ' . $order->delivery_fee,
            'address'         => $address,
            'payment_method'  => $order->payment_method,
            'payment_status'  => $order->payment_status,
            'status'          => $order->status,
            'stage'           => $stage,
            'issue'           => $order->delivery_issue_at !== null ? $order->delivery_issue_reason : null,
            'status_label'    => $meta['label'],
            'status_bg'       => $meta['bg'],
            'status_color'    => $meta['color'],
            'time_label'      => $this->timeAgo($order->created_at),
        ];
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