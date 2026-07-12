<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;

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
    public function export(): void
    {
        session()->flash('success', 'Export started.');
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

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $orders = Order::query()
            ->with(['customer', 'restaurant', 'rider'])
            ->when($this->search, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('id', 'like', "%{$this->search}%")
                        ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('restaurant', fn($r) => $r->where('name', 'like', "%{$this->search}%"))
                )
            )
            ->when($this->statusFilter, fn($q) =>
                $q->where('status', $this->statusFilter)
            )
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.order-component', [
            'orders' => $orders,
        ])->layout('layouts.admin', [
            'title'           => 'Order Management | KhaiKhai',
            'breadcrumbTitle' => 'Order Management',
        ]);
    }
}