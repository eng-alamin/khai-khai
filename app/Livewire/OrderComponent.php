<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Services\OrderNotifier;
use App\Services\OrderTransitionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    /** Real values of the orders.status enum. */
    public const STATUSES = [
        'pending', 'confirmed', 'preparing', 'ready', 'picked_up',
        'on_the_way', 'delivered', 'cancelled', 'rejected',
    ];

    /**
     * Admin may only MOVE ADMIN (product) orders forward. Vendor orders are
     * moved by the vendor (admin can still cancel them and unassign riders).
     * Riders only see orders with status "ready" and no rider yet
     * (Rider\DeliveryOngoingComponent), so admin must move preparing -> ready.
     */
    private const NEXT_STATUS = [
        'pending'   => 'confirmed',
        'confirmed' => 'preparing',
        'preparing' => 'ready',
    ];

    /** Statuses from which admin can still cancel, for vendor AND product orders (only while no rider holds it). */
    private const CANCELLABLE_FROM = ['pending', 'confirmed', 'preparing', 'ready'];

    // ── Filters ──────────────────────────────────────────────
    public string $search       = '';
    public string $statusFilter = '';
    public string $typeFilter   = '';
    public int    $perPage      = 15;

    // ── View Modal ───────────────────────────────────────────
    public ?int   $selectedOrderId = null;
    public bool   $showCancelForm  = false;
    public string $cancelReason    = '';

    // ── Watchers ─────────────────────────────────────────────
    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void   { $this->resetPage(); }

    // ── Export ───────────────────────────────────────────────
    public function export(): StreamedResponse
    {
        $orders = $this->filteredOrdersQuery()
            ->with(['customer', 'restaurant', 'rider'])
            ->get();

        $filename = 'orders-' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Order Number',
                'Type',
                'Customer',
                'Seller',
                'Rider',
                'Status',
                'Payment Status',
                'Total Amount (BDT)',
                'Placed At',
            ]);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_number,
                    $order->order_type,
                    $this->csvSafe($order->customer?->name),
                    $this->csvSafe($order->isAdminOrder() ? 'KhaiKhai Store' : $order->restaurant?->name),
                    $this->csvSafe($order->rider?->name),
                    $order->status,
                    $order->payment_status,
                    $order->total_amount,
                    $order->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /** Prevents CSV/Excel formula injection from user-controlled names. */
    private function csvSafe(?string $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)
            ? "'" . $value
            : $value;
    }

    // ── View Order ───────────────────────────────────────────
    public function viewOrder(int $id): void
    {
        $this->selectedOrderId = Order::query()->findOrFail($id)->id;
        $this->showCancelForm  = false;
        $this->cancelReason    = '';
    }

    public function closeModal(): void
    {
        $this->selectedOrderId = null;
        $this->showCancelForm  = false;
        $this->cancelReason    = '';
    }

    // ── Admin actions (advance: product orders only; cancel: all orders) ──
    public function advanceStatus(int $id): void
    {
        $order = Order::query()->adminOrders()->findOrFail($id);
        $from  = $order->status;
        $to    = self::NEXT_STATUS[$from] ?? null;

        if ($to === null) {
            $this->dispatch('show-toast', message: 'এই অর্ডারের status আর বদলানো যাবে না।', type: 'error');
            return;
        }

        // Conditional update: safe even if two admins click at the same time.
        $affected = Order::query()
            ->adminOrders()
            ->where('id', $order->id)
            ->where('status', $from)
            ->whereNull('rider_id')
            ->update(['status' => $to]);

        if ($affected === 0) {
            $this->dispatch('show-toast', message: 'অর্ডারটি ইতিমধ্যে আপডেট হয়েছে। পেজ রিফ্রেশ করুন।', type: 'error');
            return;
        }

        $this->logChange($order, $from, $to, 'Admin updated product order status');

        // The update above used the query builder, so reload before notifying.
        app(OrderNotifier::class)->statusChanged($order->refresh(), $from, $to);

        $message = match ($to) {
            'ready'  => "✅ #{$order->order_number} এখন online rider-দের কাছে দেখা যাবে।",
            default  => "✅ #{$order->order_number} এখন {$to}।",
        };

        $this->dispatch('show-toast', message: $message, type: 'success');
    }

    public function openCancelForm(): void
    {
        $this->showCancelForm = true;
        $this->cancelReason   = '';
    }

    public function cancelSelectedOrder(): void
    {
        $this->validate(
            ['cancelReason' => 'required|string|max:255'],
            ['cancelReason.required' => 'বাতিল করার কারণ লিখুন।']
        );

        if ($this->selectedOrderId === null) {
            return;
        }

        // Works for vendor (food) AND admin (product) orders.
        $order = Order::query()->findOrFail($this->selectedOrderId);

        if (! in_array($order->status, self::CANCELLABLE_FROM, true)) {
            $this->dispatch('show-toast', message: 'এই অর্ডার আর বাতিল করা যাবে না।', type: 'error');
            return;
        }

        // Same race-safe path as customer/vendor cancel: row lock, status
        // re-check, status log, coupon given back, customer + vendor notified.
        // A rider who already holds the order blocks it: unassign first.
        $cancelled = app(OrderTransitionService::class)->cancel(
            orderId: $order->id,
            scope: ['order_type' => $order->order_type],
            allowedFrom: self::CANCELLABLE_FROM,
            actor: Auth::user(),
            reason: $this->cancelReason,
            logNote: $this->cancelReason,
            activityText: 'Admin cancelled order'
        );

        if (! $cancelled) {
            $this->dispatch(
                'show-toast',
                message: 'অর্ডারটি বাতিল করা যায়নি (Rider অর্ডারটি নিয়েছে বা status বদলে গেছে)। Rider থাকলে আগে Unassign করুন।',
                type: 'error'
            );
            return;
        }

        $this->showCancelForm = false;
        $this->cancelReason   = '';
        $this->dispatch('show-toast', message: "#{$order->order_number} বাতিল করা হয়েছে।", type: 'info');
    }

    /**
     * Take an order back from a rider who accepted it but has not collected
     * the food (works for vendor AND admin orders). The order goes back to the
     * pool of available orders.
     */
    public function unassignRider(int $id): void
    {
        $done = app(OrderTransitionService::class)->unassignRider($id, Auth::user(), 'Unassigned by admin');

        if (! $done) {
            $this->dispatch('show-toast', message: 'এই অর্ডার থেকে rider সরানো যাবে না (pickup হয়ে গেছে বা rider নেই)।', type: 'error');
            return;
        }

        $this->dispatch('show-toast', message: 'Rider সরানো হয়েছে। অর্ডারটি আবার অন্য rider-দের কাছে দেখা যাবে।', type: 'success');
    }

    private function logChange(Order $order, string $from, string $to, string $note): void
    {
        DB::transaction(function () use ($order, $from, $to, $note) {
            OrderStatusLog::create([
                'order_id'    => $order->id,
                'from_status' => $from,
                'to_status'   => $to,
                'changed_by'  => Auth::id(),
                'note'        => $note,
            ]);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($order)
                ->withProperties(['from' => $from, 'to' => $to])
                ->log('Admin changed product order status');
        });
    }

    // ── Shared filtered query (used by both render() and export()) ──
    protected function filteredOrdersQuery()
    {
        $term = trim($this->search);

        return Order::query()
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';

                $q->where(function ($q2) use ($like) {
                    $q2->where('order_number', 'like', $like)
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like))
                        ->orWhereHas('restaurant', fn ($r) => $r->where('name', 'like', $like));
                });
            })
            ->when(in_array($this->statusFilter, self::STATUSES, true), fn ($q) =>
                $q->where('status', $this->statusFilter)
            )
            ->when(in_array($this->typeFilter, [Order::TYPE_VENDOR, Order::TYPE_ADMIN], true), fn ($q) =>
                $q->where('order_type', $this->typeFilter)
            )
            ->latest();
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $orders = $this->filteredOrdersQuery()
            ->with(['customer', 'restaurant', 'rider'])
            ->paginate($this->perPage);

        $selectedOrder = $this->selectedOrderId
            ? Order::with(['customer', 'restaurant', 'rider', 'items'])->find($this->selectedOrderId)
            : null;

        return view('livewire.admin.order-component', [
            'orders'        => $orders,
            'selectedOrder' => $selectedOrder,
            'nextStatus'    => self::NEXT_STATUS,
            'cancellable'   => self::CANCELLABLE_FROM,
        ])->layout('layouts.admin', [
            'title'           => 'Order Management | KhaiKhai',
            'breadcrumbTitle' => 'Order Management',
        ]);
    }
}