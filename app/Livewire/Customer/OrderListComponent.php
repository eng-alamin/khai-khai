<?php

namespace App\Livewire\Customer;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Review;
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
            ->when($this->activeFilter !== 'all', fn ($q) => $q->where('status', $this->activeFilter))
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
        $order = Order::where('customer_id', Auth::id())->findOrFail($orderId);

        // TODO: app(CartService::class)->fillFromOrder($order);
        $this->dispatch('show-toast', message: '🛒 Items added to cart!', type: 'success');
        $this->dispatch('toggle-cart');
    }

    /* ── Cancel order ── */
    public function cancelOrder(int $orderId): void
    {
        $order = Order::where('customer_id', Auth::id())->findOrFail($orderId);

        if (! in_array($order->status, Order::CANCELLABLE_STATUSES, true)) {
            $this->dispatch('show-toast', message: 'This order can no longer be cancelled.', type: 'error');
            return;
        }

        $previousStatus = $order->status;

        $order->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => 'Cancelled by customer',
        ]);

        OrderStatusLog::create([
            'order_id'    => $order->id,
            'from_status' => $previousStatus,
            'to_status'   => 'cancelled',
            'changed_by'  => Auth::id(),
            'note'        => 'Cancelled by customer from order list',
        ]);

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