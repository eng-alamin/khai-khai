<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // ── Filters ──────────────────────────────────────────────
    public string $search       = '';
    public string $statusFilter = '';
    public int    $perPage      = 15;

    // ── View Modal ───────────────────────────────────────────
    public ?Order $selectedOrder = null;

    // ── Watchers ─────────────────────────────────────────────
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

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
                'Order ID',
                'Order Number',
                'Customer',
                'Restaurant',
                'Rider',
                'Status',
                'Payment Status',
                'Total Amount (BDT)',
                'Placed At',
            ]);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->id,
                    $order->order_number,
                    $order->customer?->name,
                    $order->restaurant?->name,
                    $order->rider?->name,
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

    // ── View Order ───────────────────────────────────────────
    public function viewOrder(int $id): void
    {
        $this->selectedOrder = Order::with(['customer', 'restaurant', 'rider'])->findOrFail($id);
    }

    public function closeModal(): void
    {
        $this->selectedOrder = null;
    }

    // ── Shared filtered query (used by both render() and export()) ──
    protected function filteredOrdersQuery()
    {
        return Order::query()
            ->when($this->search, fn ($q) =>
                $q->where(fn ($q2) =>
                    $q2->where('id', 'like', "%{$this->search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('restaurant', fn ($r) => $r->where('name', 'like', "%{$this->search}%"))
                )
            )
            ->when($this->statusFilter, fn ($q) =>
                $q->where('status', $this->statusFilter)
            )
            ->latest();
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $orders = $this->filteredOrdersQuery()
            ->with(['customer', 'restaurant', 'rider'])
            ->paginate($this->perPage);

        return view('livewire.admin.order-component', [
            'orders' => $orders,
        ])->layout('layouts.admin', [
            'title'           => 'Order Management | KhaiKhai',
            'breadcrumbTitle' => 'Order Management',
        ]);
    }
}