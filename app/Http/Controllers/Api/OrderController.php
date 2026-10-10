<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PlaceOrderRequest;
use App\Http\Requests\Api\ReviewRequest;
use App\Http\Resources\FoodResource;
use App\Http\Resources\OrderResource;
use App\Models\Food;
use App\Models\Order;
use App\Models\Review;
use App\Services\CheckoutService;
use App\Services\OrderTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer orders for the mobile app. Every query starts from
 * customer_id = the signed-in user, so one customer can never read or change
 * another customer's order (a foreign order answers 404, not 403).
 */
class OrderController extends Controller
{
    /** Same as the web cart's per-item limit. */
    private const MAX_QTY = 20;

    /** Qualified columns: rider_profiles and users both have an `id` (see OrderTrackingMap). */
    private const RIDER_PROFILE_COLUMNS = 'riderProfile:rider_profiles.id,rider_profiles.user_id,rider_profiles.current_lat,rider_profiles.current_lng,rider_profiles.location_updated_at';

    public function __construct(private readonly CheckoutService $checkout)
    {
    }

    /** GET /orders?status=&search=&per_page=&page= — newest first. */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'   => ['nullable', 'string', 'max:30'],
            'search'   => ['nullable', 'string', 'max:60'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $status = (string) $request->query('status', 'all');
        $term   = trim((string) $request->query('search', ''));

        $page = Order::query()
            ->where('customer_id', $request->user()->id)
            ->with(['restaurant:id,name,latitude,longitude', 'items', 'review'])
            // "Preparing" also covers "ready", exactly like the website filter.
            ->when($status !== 'all' && $status !== '', fn ($q) => $q->whereIn(
                'status',
                $status === 'preparing' ? ['preparing', 'ready'] : [$status]
            ))
            ->when($term !== '', fn ($q) => $q->where(function ($q) use ($term) {
                $q->where('order_number', 'like', "%{$term}%")
                  ->orWhereHas('restaurant', fn ($q) => $q->where('name', 'like', "%{$term}%"));
            }))
            ->latest()
            ->latest('id')
            ->paginate((int) $request->query('per_page', 20));

        return OrderResource::collection($page)->response();
    }

    /** GET /orders/{number} — the track screen. */
    public function show(Request $request, string $number): JsonResponse
    {
        $order = Order::query()
            ->where('customer_id', $request->user()->id)
            ->where('order_number', $number)
            ->with([
                'restaurant:id,name,latitude,longitude',
                'items',
                'review',
                'rider:id,name',
                'rider.riderProfile',
                'statusLogs' => fn ($q) => $q->orderBy('created_at')->orderBy('id'),
            ])
            ->firstOrFail();

        return response()->json(['data' => (new OrderResource($order))->resolve($request)]);
    }

    /**
     * POST /orders — places a cash-on-delivery food order.
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->checkout->place(
                $request->user(),
                $request->validated('items'),
                $request->validated('coupon'),
                $request->validated('special_instructions'),
                $request->validated('platform') ?? 'android',
            );
        } catch (CheckoutException $e) {
            return response()->json($e->toPayload(), 422);
        }

        return response()->json([
            'data' => [
                'id'                    => $order->id,
                'order_number'          => $order->order_number,
                'status'                => $order->status,
                'subtotal'              => (float) $order->subtotal,
                'delivery_fee'          => (float) $order->delivery_fee,
                'discount_amount'       => (float) $order->discount_amount,
                'total_amount'          => (float) $order->total_amount,
                'payment_method'        => $order->payment_method,
                'estimated_delivery_at' => $order->estimated_delivery_at?->toIso8601String(),
                'created_at'            => $order->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * POST /orders/{order}/cancel — ownership, status and "no rider yet" are
     * re-checked under a row lock inside OrderTransitionService::cancel().
     */
    public function cancel(Request $request, OrderTransitionService $transitions, int $order): JsonResponse
    {
        $owned = Order::query()->where('customer_id', $request->user()->id)->findOrFail($order);

        $done = $transitions->cancel(
            orderId: $owned->id,
            scope: ['customer_id' => $request->user()->id],
            allowedFrom: Order::CANCELLABLE_STATUSES,
            actor: $request->user(),
            reason: 'Cancelled by customer',
            logNote: 'Cancelled by customer from the app',
            activityText: 'Customer cancelled order'
        );

        if (! $done) {
            return response()->json([
                'message' => 'This order can no longer be cancelled.',
                'code'    => 'not_cancellable',
            ], 422);
        }

        return $this->detail($request, $owned->id);
    }

    /** POST /orders/{order}/review {rating, comment?} — create or update. */
    public function review(ReviewRequest $request, int $order): JsonResponse
    {
        $owned = Order::query()->where('customer_id', $request->user()->id)->findOrFail($order);

        // reviews.restaurant_id is required, so product (admin) orders cannot be reviewed.
        if ($owned->status !== 'delivered' || $owned->restaurant_id === null) {
            return response()->json([
                'message' => 'You can only review delivered orders.',
                'code'    => 'not_reviewable',
            ], 422);
        }

        Review::updateOrCreate(
            ['order_id' => $owned->id],
            [
                'customer_id'   => $request->user()->id,
                'restaurant_id' => $owned->restaurant_id,
                'food_rating'   => (int) $request->validated('rating'),
                'comment'       => $request->validated('comment'),
            ]
        );

        return $this->detail($request, $owned->id);
    }

    /**
     * GET /orders/{number}/rider-location — polled every 8 s by the map.
     * {"data": null} until the rider has picked the food up and shared a position.
     */
    public function riderLocation(Request $request, string $number): JsonResponse
    {
        $order = Order::query()
            ->where('customer_id', $request->user()->id)
            ->where('order_number', $number)
            ->with(self::RIDER_PROFILE_COLUMNS)
            ->firstOrFail();

        $profile = $order->riderProfile;

        if ($order->rider_id === null || $order->status !== 'picked_up' || ! $profile?->current_lat) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => [
            'lat'        => (float) $profile->current_lat,
            'lng'        => (float) $profile->current_lng,
            'updated_at' => $profile->location_updated_at?->toIso8601String(),
        ]]);
    }

    /**
     * POST /orders/{order}/reorder — port of CartComponent::mergeReorderedItems().
     * Only food id + quantity come from the old order; every food is read
     * fresh (available, restaurant active + approved), so current prices win.
     * The app puts the returned foods in its cart and /cart/quote prices them.
     *
     * {"data": {"items": [{"food": {...}, "qty": 2}], "skipped": 1}}
     */
    public function reorder(Request $request, int $order): JsonResponse
    {
        $owned = Order::query()
            ->where('customer_id', $request->user()->id)
            ->with('items')
            ->findOrFail($order);

        $wanted = [];
        foreach ($owned->items as $item) {
            if ($item->orderable_type === Food::class && $item->orderable_id) {
                $wanted[$item->orderable_id] = ($wanted[$item->orderable_id] ?? 0) + max(1, (int) $item->quantity);
            }
        }

        if ($wanted === []) {
            return response()->json([
                'message' => 'This order has no items to reorder.',
                'code'    => 'no_items',
            ], 422);
        }

        $foods = Food::query()
            ->whereIn('id', array_keys($wanted))
            ->where('is_available', true)
            ->whereHas('restaurant', fn ($q) => $q->where('is_active', true)->where('is_approved', true))
            ->with('restaurant:id,name,slug')
            ->get()
            ->keyBy('id');

        if ($foods->isEmpty()) {
            return response()->json([
                'message' => 'These items are no longer available.',
                'code'    => 'items_unavailable',
            ], 422);
        }

        // One restaurant per cart: keep the restaurant of the first item that still exists.
        $restaurantId = null;
        $items = [];

        foreach ($wanted as $id => $qty) {
            $food = $foods->get($id);

            if (! $food) {
                continue;
            }

            $restaurantId ??= $food->restaurant_id;

            if ($food->restaurant_id !== $restaurantId) {
                continue;
            }

            $items[] = [
                'food' => (new FoodResource($food))->resolve($request),
                'qty'  => min(self::MAX_QTY, $qty),
            ];
        }

        return response()->json(['data' => [
            'items'   => $items,
            'skipped' => count($wanted) - count($items),
        ]]);
    }

    /** Fresh detail payload (same shape as show()) after cancel / review. */
    private function detail(Request $request, int $orderId): JsonResponse
    {
        $order = Order::query()
            ->where('customer_id', $request->user()->id)
            ->with([
                'restaurant:id,name,latitude,longitude',
                'items',
                'review',
                'rider:id,name',
                'rider.riderProfile',
                'statusLogs' => fn ($q) => $q->orderBy('created_at')->orderBy('id'),
            ])
            ->findOrFail($orderId);

        return response()->json(['data' => (new OrderResource($order))->resolve($request)]);
    }
}
