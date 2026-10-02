<?php

namespace App\Livewire\Customer;

use App\Models\Order;
use App\Models\Review;
use App\Services\OrderTransitionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class OrderListComponent extends Component
{
    use WithPagination;

    public string $activeFilter = 'all';
    public string $searchQuery  = '';

    // Review modal state
    public ?int   $reviewOrderId = null;
    public int    $reviewRating  = 0;
    public string $reviewComment = '';

    public array $filters = [
        ['key' => 'all',        'label' => 'All Orders', 'emoji' => '📋'],
        ['key' => 'pending',    'label' => 'Pending',    'emoji' => '⏳'],
        ['key' => 'confirmed',  'label' => 'Confirmed',  'emoji' => '👍'],
        ['key' => 'preparing',  'label' => 'Preparing',  'emoji' => '🍳'],
        ['key' => 'picked_up',  'label' => 'On the Way', 'emoji' => '🛵'],
        ['key' => 'delivered',  'label' => 'Delivered',  'emoji' => '✅'],
        ['key' => 'cancelled',  'label' => 'Cancelled',  'emoji' => '❌'],
    ];

    protected $queryString = ['activeFilter' => ['except' => 'all']];

    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    /* ── Computed: filtered + searched orders (ডাটাবেস থেকে, paginated) ── */
    public function getFilteredOrdersProperty()
    {
        return Order::query()
            ->where('customer_id', Auth::id())
            ->with(['restaurant', 'items', 'review'])
            ->when($this->activeFilter !== 'all', fn ($q) => $q->whereIn('status', $this->activeFilter === 'preparing' ? ['preparing', 'ready'] : [$this->activeFilter]))
            ->when(trim($this->searchQuery) !== '', function ($q) {
                $term = trim($this->searchQuery);
                $q->where(function ($q) use ($term) {
                    $q->where('order_number', 'like', "%{$term}%")
                      ->orWhereHas('restaurant', fn ($q) => $q->where('name', 'like', "%{$term}%"));
                });
            })
            ->latest()
            ->paginate(8);
    }

    /* ── Filter tab click ── */
    public function setFilter(string $key): void
    {
        $this->activeFilter = $key;
        $this->searchQuery  = '';
        $this->resetPage();
    }

    /* ── Reorder ── */
    public function reorder(int $orderId): void
    {
        $order = Order::where('customer_id', Auth::id())
            ->with('items')
            ->findOrFail($orderId);

        // Reorder by orderable_id + quantity only. Each cart re-verifies live
        // price/availability itself (same as at checkout), so we never trust
        // the old order's snapshot price here.
        // Food (vendor) -> CartComponent, Product (admin) -> ProductCartComponent.
        $toPayload = fn (string $type) => $order->items
            ->where('orderable_type', $type)
            ->whereNotNull('orderable_id')
            ->map(fn ($item) => ['id' => $item->orderable_id, 'qty' => $item->quantity])
            ->values()
            ->all();

        $foods    = $toPayload(\App\Models\Food::class);
        $products = $toPayload(\App\Models\Product::class);

        if (empty($foods) && empty($products)) {
            $this->dispatch('show-toast', message: 'This order has no items to reorder.', type: 'error');
            return;
        }

        if (! empty($foods)) {
            $this->dispatch('reorder-items', items: $foods);
        }

        if (! empty($products)) {
            $this->dispatch('reorder-products', items: $products);
        }
    }

    /* ── Cancel order ── */
    public function cancelOrder(int $orderId): void
    {
        // Ownership + status are checked again under a row lock inside the service.
        $cancelled = app(OrderTransitionService::class)->cancel(
            orderId: $orderId,
            scope: ['customer_id' => Auth::id()],
            allowedFrom: Order::CANCELLABLE_STATUSES,
            actor: Auth::user(),
            reason: 'Cancelled by customer',
            logNote: 'Cancelled by customer from order list',
            activityText: 'Customer cancelled order'
        );

        if (! $cancelled) {
            $this->dispatch('show-toast', message: 'This order can no longer be cancelled.', type: 'error');
            return;
        }

        $this->dispatch('show-toast', message: 'Order has been cancelled.', type: 'info');
    }

    /* ── Open review modal ── */
    public function openReviewModal(int $orderId): void
    {
        $order = Order::where('customer_id', Auth::id())->findOrFail($orderId);

        if ($order->status !== 'delivered') {
            $this->dispatch('show-toast', message: 'You can only review delivered orders.', type: 'error');
            return;
        }

        $this->reviewOrderId = $order->id;
        $this->reviewRating  = $order->review?->food_rating ?? 0;
        $this->reviewComment = $order->review?->comment ?? '';
    }

    /* ── Set star rating ── */
    public function setRating(int $value): void
    {
        $this->reviewRating = $value;
    }

    /* ── Submit review ── */
    public function submitReview(): void
    {
        $this->validate([
            'reviewRating'  => 'required|integer|min:1|max:5',
            'reviewComment' => 'nullable|string|max:500',
        ], [
            'reviewRating.min' => 'Please select a rating!',
        ]);

        $order = Order::where('customer_id', Auth::id())
            ->where('status', 'delivered')
            ->findOrFail($this->reviewOrderId);

        Review::updateOrCreate(
            ['order_id' => $order->id],
            [
                'customer_id'   => Auth::id(),
                'restaurant_id' => $order->restaurant_id,
                'food_rating'        => $this->reviewRating,
                'comment'       => $this->reviewComment,
            ]
        );

        $this->reset(['reviewOrderId', 'reviewRating', 'reviewComment']);
        $this->dispatch('show-toast', message: '⭐ Review submitted successfully!', type: 'success');
    }

    public function render()
    {
        return view('livewire.customer.order-list-component', [
            'filteredOrders' => $this->filteredOrders,
        ])->layout('layouts.customer', [
            'title'           => 'Orders | KhaiKhai',
            'breadcrumbTitle' => 'My Orders',
        ]);
    }
}