<?php

namespace App\Livewire\Vendor;

use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrderLiveComponent extends Component
{
    /** এই স্ট্যাটাসগুলোকে "লাইভ" (এখনো চলমান) ধরা হবে */
    private const LIVE_STATUSES = ['pending', 'confirmed', 'preparing', 'picked_up'];

    /** Forward-only workflow (OrderListComponent-এর সাথে সামঞ্জস্যপূর্ণ) */
    private const STATUS_FLOW = ['pending', 'confirmed', 'preparing', 'picked_up', 'delivered'];

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
        return Auth::user()->restaurant->id;
    }

    // ── Status meta (OrderListComponent প্যাটার্ন অনুসরণ) ──
    public function statusMeta(string $status): array
    {
        return match ($status) {
            'pending'   => ['label' => 'Pending',    'emoji' => '🕐', 'class' => 'pending'],
            'confirmed' => ['label' => 'Confirmed',  'emoji' => '✅', 'class' => 'confirmed'],
            'preparing' => ['label' => 'Preparing',  'emoji' => '👨‍🍳', 'class' => 'preparing'],
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
            'picked_up' => 'Mark Picked Up',
            'delivered' => 'Mark Delivered',
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
        $this->isOnline = ! $this->isOnline;
        // TODO: Restaurant::find($this->restaurantId())->update(['is_open' => $this->isOnline]);
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

    /* ── Generic forward-advance (pending→confirmed, confirmed→preparing, preparing→picked_up, picked_up→delivered) ── */
    public function advanceStatus(int $id): void
    {
        $order = Order::where('restaurant_id', $this->restaurantId())->findOrFail($id);
        $next  = $this->nextStatus($order->status);

        if (! $next) {
            return;
        }

        $from = $order->status;

        $order->status = $next;
        if ($next === 'delivered') {
            $order->delivered_at = now();
        }
        $order->save();

        $this->logStatus($order, $from, $next);

        session()->flash('success', "Order #{$order->order_number} marked as {$this->statusMeta($next)['label']}.");
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

        if ($order->status !== 'pending') {
            $this->confirmReject = false;
            session()->flash('error', 'এই অর্ডারটি আর বাতিল করা যাবে না।');
            return;
        }

        $from = $order->status;

        $order->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => $this->reject_reason,
        ]);

        $this->logStatus($order, $from, 'cancelled', $this->reject_reason);

        $this->confirmReject = false;
        $this->rejectId      = null;
        $this->reject_reason = '';

        session()->flash('success', "Order #{$order->order_number} cancelled.");
    }

    private function logStatus(Order $order, string $from, string $to, ?string $note = null): void
    {
        OrderStatusLog::create([
            'order_id'    => $order->id,
            'from_status' => $from,
            'to_status'   => $to,
            'changed_by'  => Auth::id(),
            'note'        => $note,
        ]);
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