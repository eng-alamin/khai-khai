<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Order;

class CustomerComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // ── Filters ──────────────────────────────────────────────
    public string $search     = '';
    public string $sortBy     = 'total_orders';
    public int    $perPage    = 15;

    // ── Watchers ─────────────────────────────────────────────
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingSortBy(): void { $this->resetPage(); }

    // ── Export ───────────────────────────────────────────────
    public function export(): void
    {
        session()->flash('success', 'Export started.');
    }

    // ── Computed: Stats ───────────────────────────────────────
    public function getStatsProperty(): array
    {
        $currentMonth = now()->startOfMonth();

        $total = User::where('role', 'customer')->count();

        $newThisMonth = User::where('role', 'customer')
            ->where('created_at', '>=', $currentMonth)
            ->count();

        $totalWithOrders = User::where('role', 'customer')
            ->whereHas('orders', fn($q) => $q->where('status', 'delivered'))
            ->count();

        $repeatCustomers = User::where('role', 'customer')
            ->whereHas('orders', fn($q) => $q->where('status', 'delivered'), '>', 1)
            ->count();

        $repeatRate = $totalWithOrders > 0
            ? round(($repeatCustomers / $totalWithOrders) * 100)
            : 0;

        $avgOrderValue = Order::where('status', 'delivered')->avg('total_amount') ?? 0;

        return [
            'total'           => number_format($total),
            'new_this_month'  => number_format($newThisMonth),
            'repeat_rate'     => $repeatRate,
            'avg_order_value' => intdiv((int) $avgOrderValue, 100),
        ];
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $customers = User::where('role', 'customer')
            ->with([
                'customerProfile.defaultAddress',
            ])
            ->withCount(['orders as total_orders' => fn($q) => $q->where('status', 'delivered')])
            ->withSum(['orders as total_spent' => fn($q) => $q->where('status', 'delivered')], 'total_amount')
            ->when($this->search, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                )
            )
            ->orderBy($this->sortBy === 'total_spent' ? 'total_spent' : 'total_orders', 'desc')
            ->paginate($this->perPage);

        return view('livewire.admin.customer-component', [
            'customers' => $customers,
            'stats'     => $this->stats,
        ])->layout('layouts.admin', [
            'title'           => 'Customer Management | KhaiKhai',
            'breadcrumbTitle' => 'Customer Management',
        ]);
    }
}