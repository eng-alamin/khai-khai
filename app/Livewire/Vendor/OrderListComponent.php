<?php

namespace App\Livewire\Vendor;

use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class OrderListComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    /** Forward-only workflow. */
    private const STATUS_FLOW = ['pending', 'confirmed', 'preparing', 'picked_up', 'delivered'];

    // ── List / Filter ─────────────────────────────────────
    public string $search        = '';
    public int    $perPage       = 10;
    public string $sortField     = 'created_at';
    public string $sortDirection = 'desc';
    public string $filterStatus  = '';
    public string $filterPayment = '';
    public string $filterDate    = '';

    // ── Details Modal ──────────────────────────────────────
    public bool $showDetailsModal = false;
    public ?int $detailsId        = null;

    // ── Cancel Modal ────────────────────────────────────────
    public bool   $confirmCancel = false;
    public ?int   $cancelId      = null;
    public string $cancel_reason = '';

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

    // ── Status helpers ─────────────────────────────────────
    public function statusMeta(string $status): array
    {
        return match ($status) {
            'pending'   => ['label' => 'Pending',   'emoji' => '🕐', 'class' => 'pending'],
            'confirmed' => ['label' => 'Confirmed', 'emoji' => '✅', 'class' => 'confirmed'],
            'preparing' => ['label' => 'Preparing', 'emoji' => '👨‍🍳', 'class' => 'preparing'],
            'picked_up' => ['label' => 'Picked Up', 'emoji' => '🛵', 'class' => 'picked-up'],
            'delivered' => ['label' => 'Delivered', 'emoji' => '📦', 'class' => 'delivered'],
            'cancelled' => ['label' => 'Cancelled', 'emoji' => '❌', 'class' => 'cancelled'],
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

    public function isCancellable(string $status): bool
    {
        return in_array($status, ['pending', 'confirmed', 'preparing'], true);
    }

    // ── Watchers ─────────────────────────────────────────
    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingFilterStatus(): void  { $this->resetPage(); }
    public function updatingFilterPayment(): void { $this->resetPage(); }
    public function updatingFilterDate(): void    { $this->resetPage(); }

    // ── Sorting ───────────────────────────────────────────
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'desc';
        }
        $this->resetPage();
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

    // ── Advance status ──────────────────────────────────────
    public function advanceStatus(int $id): void
    {
        $order = Order::where('restaurant_id', $this->restaurantId())->findOrFail($id);
        $next  = $this->nextStatus($order->status);

        if (! $next) {
            return;
        }

        $from = $order->status;

        DB::transaction(function () use ($order, $from, $next) {
            $order->status = $next;
            if ($next === 'delivered') {
                $order->delivered_at = now();
            }
            $order->save();

            OrderStatusLog::create([
                'order_id'    => $order->id,
                'from_status' => $from,
                'to_status'   => $next,
                'changed_by'  => Auth::id(),
            ]);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($order)
                ->withProperties(['from' => $from, 'to' => $next])
                ->log('Vendor advanced order status');
        });

        session()->flash('success', "Order #{$order->order_number} marked as {$this->statusMeta($next)['label']}.");
    }

    // ── Cancel flow ─────────────────────────────────────────
    public function confirmCancelRecord(int $id): void
    {
        $this->cancelId      = $id;
        $this->cancel_reason = '';
        $this->confirmCancel = true;
    }

    public function cancelOrder(): void
    {
        if (! $this->cancelId) {
            $this->confirmCancel = false;
            return;
        }

        $this->validate(
            ['cancel_reason' => 'required|string|max:255'],
            ['cancel_reason.required' => 'Please tell the customer why this order is being cancelled.']
        );

        $order = Order::where('restaurant_id', $this->restaurantId())->findOrFail($this->cancelId);

        if (! $this->isCancellable($order->status)) {
            $this->confirmCancel = false;
            session()->flash('error', 'This order can no longer be cancelled.');
            return;
        }

        $from = $order->status;

        DB::transaction(function () use ($order, $from) {
            $order->update([
                'status'        => 'cancelled',
                'cancelled_at'  => now(),
                'cancel_reason' => $this->cancel_reason,
            ]);

            OrderStatusLog::create([
                'order_id'    => $order->id,
                'from_status' => $from,
                'to_status'   => 'cancelled',
                'changed_by'  => Auth::id(),
                'note'        => $this->cancel_reason,
            ]);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($order)
                ->withProperties(['from' => $from, 'to' => 'cancelled', 'reason' => $this->cancel_reason])
                ->log('Vendor cancelled order');
        });

        $this->confirmCancel = false;
        $this->cancelId      = null;
        $this->cancel_reason = '';

        session()->flash('success', 'Order cancelled and the customer will be notified.');
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        $orders = Order::query()
            ->where('restaurant_id', $this->restaurantId())
            ->with(['customer', 'items'])
            ->when($this->search, function ($q) {
                $term = $this->search;
                $q->where(function ($qq) use ($term) {
                    $qq->where('order_number', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($c) =>
                            $c->where('name', 'like', "%{$term}%")
                        );
                });
            })
            ->when($this->filterStatus, fn ($q) =>
                $q->where('status', $this->filterStatus)
            )
            ->when($this->filterPayment, fn ($q) =>
                $q->where('payment_status', $this->filterPayment)
            )
            ->when($this->filterDate === 'today', fn ($q) =>
                $q->whereDate('created_at', today())
            )
            ->when($this->filterDate === 'week', fn ($q) =>
                $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            )
            ->when($this->filterDate === 'month', fn ($q) =>
                $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.vendor.order-list-component', [
            'orders'       => $orders,
            'detailsOrder' => $this->getDetailsOrder(),
        ])->layout('layouts.vendor', [
            'title'           => 'All Orders | KhaiKhai',
            'breadcrumbTitle' => 'Orders',
        ]);
    }
}