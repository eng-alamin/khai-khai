<?php

namespace App\Livewire\Vendor;

use App\Models\Order;
use App\Models\Restaurant;
use App\Services\OrderTransitionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrderLiveComponent extends Component
{
    /** এই স্ট্যাটাসগুলোকে "লাইভ" (এখনো চলমান) ধরা হবে */
    private const LIVE_STATUSES = ['pending', 'confirmed', 'preparing', 'ready', 'picked_up'];

    /** Forward-only workflow (OrderListComponent-এর সাথে সামঞ্জস্যপূর্ণ) */
    private const STATUS_FLOW = ['pending', 'confirmed', 'preparing', 'ready'];

    public bool $isOnline = true;

    // ── Details Modal ──────────────────────────────────────
    public bool $showDetailsModal = false;
    public ?int $detailsId        = null;

    // ── Reject Modal ─────────────────────────────────────
    public bool   $confirmReject = false;
    public ?int   $rejectId      = null;
    public string $reject_reason = '';

    // ── Restaurant helper ─────────────────────────────────
    private function restaurantId(): int
    {
        $restaurant = Auth::user()->restaurant;

        abort_if(
            ! $restaurant,
            403,
            'No restaurant is linked to your account yet. Please contact support.'
        );

        return $restaurant->id;
    }

    public function mount(): void
    {
        // Reflect the restaurant's real open/closed state instead of
        // always defaulting to "online" regardless of DB value.
        $this->isOnline = (bool) Restaurant::findOrFail($this->restaurantId())->is_open;
    }

    // ── Status meta (OrderListComponent প্যাটার্ন অনুসরণ) ──
    public function statusMeta(string $status): array
    {
        return match ($status) {
            'pending'   => ['label' => 'Pending',    'emoji' => '🕐', 'class' => 'pending'],
            'confirmed' => ['label' => 'Confirmed',  'emoji' => '✅', 'class' => 'confirmed'],
            'preparing' => ['label' => 'Preparing',  'emoji' => '👨‍🍳', 'class' => 'preparing'],
            'ready'     => ['label' => 'Ready for Pickup', 'emoji' => '✅', 'class' => 'preparing'],
            'picked_up' => ['label' => 'Picked Up',  'emoji' => '🛵', 'class' => 'picked-up'],
            'delivered' => ['label' => 'Delivered',  'emoji' => '📦', 'class' => 'delivered'],
            'cancelled' => ['label' => 'Cancelled',  'emoji' => '❌', 'class' => 'cancelled'],
            default     => ['label' => ucfirst($status), 'emoji' => '•', 'class' => 'pending'],
        };
    }

    public function paymentMeta(?string $status): array
    {
        return match ($status ?? '') {
            'paid'     => ['label' => 'Paid',     'class' => 'paid'],
            'refunded' => ['label' => 'Refunded', 'class' => 'refunded'],
            'failed'   => ['label' => 'Failed',   'class' => 'failed'],
            default    => ['label' => 'Pending',  'class' => 'pending'],
        };
    }

    public function paymentMethodLabel(?string $method): string
    {
        return match ($method ?? '') {
            'bkash'            => 'bKash',
            'nagad'            => 'Nagad',
            'card'             => 'Card',
            'cash_on_delivery' => 'Cash on Delivery',
            default            => $method ? ucfirst($method) : '—',
        };
    }

    public function nextStatus(string $current): ?string
    {
        $i = array_search($current, self::STATUS_FLOW, true);

        if ($i === false || $i === count(self::STATUS_FLOW) - 1) {
            return null;
        }

        return self::STATUS_FLOW[$i + 1];
    }

    public function nextActionLabel(string $current): ?string
    {
        return match ($this->nextStatus($current)) {
            'confirmed' => 'Confirm Order',
            'preparing' => 'Start Preparing',
            'ready'     => 'Mark Ready',
            default     => null,
        };
    }

    /* ── Computed: এই রেস্তোরাঁর চলমান অর্ডারগুলো, সবচেয়ে পুরোনোটা আগে ── */
    public function getLiveOrdersProperty()
    {
        return Order::with(['items', 'customer'])
            ->where('restaurant_id', $this->restaurantId())
            ->whereIn('status', self::LIVE_STATUSES)
            ->latest()
            ->get();
    }

    public function getLiveCountProperty(): int
    {
        return $this->liveOrders->count();
    }

    public function toggleOnline(): void
    {
        $restaurant = Restaurant::findOrFail($this->restaurantId());
        $restaurant->update(['is_open' => ! $restaurant->is_open]);

        $this->isOnline = (bool) $restaurant->is_open;

        activity()
            ->causedBy(Auth::user())
            ->performedOn($restaurant)
            ->withProperties(['is_open' => $this->isOnline])
            ->log($this->isOnline ? 'Vendor opened restaurant' : 'Vendor closed restaurant');

        $this->dispatch(
            'show-toast',
            message: $this->isOnline ? 'Restaurant is now Open 🟢' : 'Restaurant is now Closed 🔴',
            type: $this->isOnline ? 'success' : 'warning'
        );
    }

    // ── Details modal ──────────────────────────────────────
    public function openDetails(int $id): void
    {
        $this->detailsId        = $id;
        $this->showDetailsModal = true;
    }

    public function closeDetails(): void
    {
        $this->showDetailsModal = false;
        $this->detailsId        = null;
    }

    // ── Load order for details modal ───────────────────────
    private function getDetailsOrder(): ?Order
    {
        if (! $this->showDetailsModal || ! $this->detailsId) {
            return null;
        }

        return Order::where('restaurant_id', $this->restaurantId())
            ->with(['customer', 'rider', 'items', 'statusLogs.changedBy'])
            ->find($this->detailsId);
    }

    /* ── Forward-advance (pending→confirmed→preparing→ready). Rider takes over after "ready". ── */
    public function advanceStatus(int $id): void
    {
        $order = Order::where('restaurant_id', $this->restaurantId())->findOrFail($id);

        if (! $this->nextStatus($order->status)) {
            return;
        }

        $newStatus = app(OrderTransitionService::class)->advance(
            orderId: $order->id,
            scope: ['restaurant_id' => $this->restaurantId()],
            expectedFrom: $order->status,
            actor: Auth::user(),
            activityText: 'Vendor advanced live order status'
        );

        if ($newStatus === null) {
            session()->flash('error', 'This order was already updated. Please refresh the list.');
            return;
        }

        session()->flash('success', "Order #{$order->order_number} marked as {$this->statusMeta($newStatus)['label']}.");
    }

    /* ── Reject flow (শুধু pending অর্ডারের জন্য) ── */
    public function confirmRejectRecord(int $id): void
    {
        $this->rejectId      = $id;
        $this->reject_reason = '';
        $this->confirmReject = true;
    }

    public function rejectOrder(): void
    {
        if (! $this->rejectId) {
            $this->confirmReject = false;
            return;
        }

        $this->validate(
            ['reject_reason' => 'required|string|max:255'],
            ['reject_reason.required' => 'কেন বাতিল করছেন তা কাস্টমারকে জানান।']
        );

        $order = Order::where('restaurant_id', $this->restaurantId())->findOrFail($this->rejectId);

        $cancelled = app(OrderTransitionService::class)->cancel(
            orderId: $order->id,
            scope: ['restaurant_id' => $this->restaurantId()],
            allowedFrom: ['pending'],
            actor: Auth::user(),
            reason: $this->reject_reason,
            logNote: $this->reject_reason,
            activityText: 'Vendor rejected order'
        );

        if (! $cancelled) {
            $this->confirmReject = false;
            $this->rejectId      = null;
            session()->flash('error', 'এই অর্ডারটি আর বাতিল করা যাবে না।');
            return;
        }

        $this->confirmReject = false;
        $this->rejectId      = null;
        $this->reject_reason = '';

        session()->flash('success', "Order #{$order->order_number} cancelled.");
    }

    public function toBanglaNumber(int|string $number): string
    {
        static $digits = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
        return strtr((string) $number, $digits);
    }

    public function render()
    {
        return view('livewire.vendor.order-live-component', [
            'liveOrders'   => $this->liveOrders,
            'detailsOrder' => $this->getDetailsOrder(),
        ])->layout('layouts.vendor', [
            'title'           => 'Live Orders | KhaiKhai',
            'breadcrumbTitle' => 'Live Orders',
        ]);
    }
}