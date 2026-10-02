<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use App\Models\Food;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Restaurant;
use App\Services\CouponService;
use App\Models\AdminSetting;
use App\Services\DeliveryChargeService;
use App\Services\OrderNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class CartComponent extends Component
{
    /** Maximum quantity of a single item in one order. */
    private const MAX_QTY_PER_ITEM = 20;

    /**
     * Locked: the browser cannot change the cart directly. Only the methods
     * below (addItem, increment, ...) can, so quantity and restaurant rules
     * cannot be bypassed from the browser console.
     */
    #[Locked]
    #[Session(key: 'kk_cart')]
    public array $items = [];

    public bool  $open         = false;
    public bool  $showConflict = false;

    #[Locked]
    public array $pendingItem  = [];

    // ── Coupon ────────────────────────────────────────────
    /** Applied code only. Price/validity are re-checked on every render and at checkout. */
    #[Session(key: 'kk_cart_coupon')]
    public string $couponCode = '';

    public string $couponInput = '';
    public string $couponError = '';


    // ─────────────────────────────────────────
    // ONE CART, ONE SOURCE
    // A cart holds EITHER items of a single restaurant (kind = food)
    // OR admin products (kind = product, restaurant_id = null).
    // Adding from a different source asks the customer first.
    // ─────────────────────────────────────────

    /** "admin" for admin products, "r{id}" for a restaurant. */
    private function sourceKeyOf(array $item): string
    {
        return ($item['kind'] ?? 'food') === 'product'
            ? 'admin'
            : 'r' . (int) ($item['restaurant_id'] ?? 0);
    }

    private function currentSourceKey(): ?string
    {
        $first = collect($this->items)->first();

        return $first ? $this->sourceKeyOf($first) : null;
    }

    /** 'food' | 'product' | null (empty cart). Old sessions have no kind => food. */
    private function cartKind(): ?string
    {
        $first = collect($this->items)->first();

        if (! $first) {
            return null;
        }

        return ($first['kind'] ?? 'food') === 'product' ? 'product' : 'food';
    }

    private function conflictsWith(string $sourceKey): bool
    {
        $current = $this->currentSourceKey();

        return $current !== null && $current !== $sourceKey;
    }

    /** Fixed delivery fee (Taka) for admin product orders. */
    private function productDeliveryFee(): float
    {
        return (float) AdminSetting::get('product_delivery_fee', 49);
    }

    #[On('add-to-cart')]
    public function addItem(int $id, string $name = '', float $price = 0): void
    {
        $menuItem = Food::find($id);
        if (! $menuItem) return;

        if ($this->conflictsWith('r' . (int) $menuItem->restaurant_id)) {
            $this->pendingItem = [
                'kind'          => 'food',
                'id'            => $menuItem->id,
                'name'          => $menuItem->name,
                'price'         => $menuItem->price,
                'image_url'     => $menuItem->image_url,
                'emoji'         => $menuItem->emoji ?? null,
                'restaurant_id' => $menuItem->restaurant_id,
            ];
            $this->showConflict = true;
            return;
        }

        $this->doAddItem($menuItem);
    }

    /**
     * Admin product ("Featured Products"). Called from the product detail
     * page. Only the ID is trusted from the browser: name and price are
     * always read fresh from the database.
     */
    #[On('add-product-to-cart')]
    public function addProduct(int $id): void
    {
        $product = Product::query()->active()->find($id);

        if (! $product) {
            $this->dispatch('notify', message: 'এই প্রোডাক্টটি আর পাওয়া যাচ্ছে না।', type: 'error');
            return;
        }

        if ($this->conflictsWith('admin')) {
            $this->pendingItem = [
                'kind'          => 'product',
                'id'            => $product->id,
                'name'          => $product->name,
                'price'         => (float) $product->price,
                'image_url'     => $product->image_url,
                'emoji'         => $product->emoji,
                'restaurant_id' => null,
            ];
            $this->showConflict = true;
            return;
        }

        $this->doAddProduct($product);
        $this->dispatch('notify', message: '🛒 কার্টে যোগ হয়েছে!', type: 'success');
    }

    /**
     * FEATURE: implements the previously-stubbed "Reorder" button on the
     * customer order list. OrderListComponent::reorder() dispatches this
     * with [{id, qty}, ...] from the old order — we deliberately only take
     * the menu_item_id + quantity from that payload and re-fetch each item
     * fresh here, so price/availability/restaurant status is always
     * current, never trusted from the old order snapshot.
     */
    #[On('reorder-items')]
    public function mergeReorderedItems(array $items): void
    {
        $ids = array_column($items, 'id');

        $menuItems = Food::whereIn('id', $ids)
            ->where('is_available', true)
            ->whereHas('restaurant', fn ($q) => $q->where('is_active', true)->where('is_approved', true))
            ->get()
            ->keyBy('id');

        if ($menuItems->isEmpty()) {
            $this->dispatch('show-toast', message: 'These items are no longer available.', type: 'error');
            return;
        }

        $restaurantId = $menuItems->first()->restaurant_id;

        // Same single-restaurant-per-cart rule as addItem(): if the cart
        // already holds items from a different restaurant, replace it
        // rather than silently mixing two restaurants' items together.
        if ($this->conflictsWith('r' . (int) $restaurantId)) {
            $this->items      = [];
            $this->couponCode = '';
        }

        $skipped = 0;

        foreach ($items as $row) {
            $menuItem = $menuItems->get($row['id']);

            if (! $menuItem || $menuItem->restaurant_id !== $restaurantId) {
                $skipped++;
                continue;
            }

            $this->doAddItem($menuItem, max(1, (int) $row['qty']));
        }

        $this->open = true;

        if ($skipped > 0) {
            $this->dispatch('show-toast', message: "Some items were unavailable and skipped ({$skipped}).", type: 'info');
        } else {
            $this->dispatch('show-toast', message: '🛒 Items added to cart!', type: 'success');
        }
    }

    /**
     * Reorder for admin products. Price and availability are always
     * re-read from the database; the old order's snapshot is not trusted.
     */
    #[On('reorder-products')]
    public function mergeReorderedProducts(array $items): void
    {
        $ids = array_map('intval', array_column($items, 'id'));

        $products = Product::query()->active()->whereIn('id', $ids)->get()->keyBy('id');

        if ($products->isEmpty()) {
            $this->dispatch('notify', message: 'এই প্রোডাক্টগুলো আর পাওয়া যাচ্ছে না।', type: 'error');
            return;
        }

        // Same single-source rule: a restaurant cart is replaced, not mixed.
        if ($this->conflictsWith('admin')) {
            $this->items      = [];
            $this->couponCode = '';
        }

        $skipped = 0;

        foreach ($items as $row) {
            $product = $products->get((int) ($row['id'] ?? 0));

            if (! $product) {
                $skipped++;
                continue;
            }

            $this->doAddProduct($product, max(1, (int) ($row['qty'] ?? 1)));
        }

        $this->open = true;

        $message = $skipped > 0
            ? "কিছু প্রোডাক্ট পাওয়া যায়নি ({$skipped}টি বাদ গেছে)।"
            : '🛒 প্রোডাক্ট কার্টে যোগ হয়েছে!';

        $this->dispatch('notify', message: $message, type: $skipped > 0 ? 'info' : 'success');
    }

    public function confirmClearAndAdd(): void
    {
        $pending = $this->pendingItem;

        $this->items        = [];
        $this->couponCode   = '';
        $this->couponInput  = '';
        $this->couponError  = '';
        $this->open         = false;
        $this->showConflict = false;
        $this->pendingItem  = [];

        if (empty($pending)) {
            return;
        }

        if (($pending['kind'] ?? 'food') === 'product') {
            $product = Product::query()->active()->find($pending['id']);
            if ($product) {
                $this->doAddProduct($product);
            }
            return;
        }

        $menuItem = Food::find($pending['id']);
        if ($menuItem) {
            $this->doAddItem($menuItem);
        }
    }

    public function cancelConflict(): void
    {
        $this->showConflict = false;
        $this->pendingItem  = [];
    }

    private function doAddItem(Food $menuItem, int $qty = 1): void
    {
        if (isset($this->items[$menuItem->id])) {
            $this->bumpQty($menuItem->id, $qty);
            return;
        }

        $this->items[$menuItem->id] = [
            'kind'          => 'food',
            'name'          => $menuItem->name,
            'price'         => $menuItem->price,
            'qty'           => $this->clampQty($qty),
            'image_url'     => $menuItem->image_url,
            'emoji'         => $menuItem->emoji ?? null,
            'restaurant_id' => $menuItem->restaurant_id,
        ];
    }

    private function doAddProduct(Product $product, int $qty = 1): void
    {
        if (isset($this->items[$product->id])) {
            $this->bumpQty($product->id, $qty);
            return;
        }

        $this->items[$product->id] = [
            'kind'          => 'product',
            'name'          => $product->name,
            'price'         => (float) $product->price,
            'qty'           => $this->clampQty($qty),
            'image_url'     => $product->image_url,
            'emoji'         => $product->emoji,
            'restaurant_id' => null,
        ];
    }

    public function increment(int $id): void
    {
        if (isset($this->items[$id])) {
            $this->bumpQty($id, 1);
        }
    }

    /** Keep quantity a whole number between 1 and MAX_QTY_PER_ITEM. */
    private function clampQty(int|float|string $qty): int
    {
        return max(1, min(self::MAX_QTY_PER_ITEM, (int) $qty));
    }

    private function bumpQty(int $id, int $by): void
    {
        $wanted = (int) $this->items[$id]['qty'] + $by;

        if ($wanted > self::MAX_QTY_PER_ITEM) {
            $this->dispatch(
                'show-toast',
                message: 'একটি আইটেম সর্বোচ্চ ' . self::MAX_QTY_PER_ITEM . 'টি অর্ডার করা যাবে।',
                type: 'info'
            );
        }

        $this->items[$id]['qty'] = $this->clampQty($wanted);
    }

    /** Delivery fee used only when the distance cannot be calculated. Admin-editable. */
    private function fallbackDeliveryFee(): float
    {
        return (float) AdminSetting::get('fallback_delivery_fee', 49);
    }

    public function decrement(int $id): void
    {
        if (! isset($this->items[$id])) return;

        if ($this->items[$id]['qty'] <= 1) {
            $this->removeItem($id);
            return;
        }
        $this->items[$id]['qty']--;
    }

    public function removeItem(int $id): void
    {
        unset($this->items[$id]);
    }

    public function clearCart(): void
    {
        $this->items        = [];
        $this->couponCode   = '';
        $this->couponInput  = '';
        $this->couponError  = '';
        $this->open         = false;
        $this->showConflict = false;
        $this->pendingItem  = [];
    }

    public function toggleCart(): void
    {
        $this->open = ! $this->open;
    }

    public function getSubtotalProperty(): float
    {
        return round(array_sum(array_map(
            fn($i) => $i['price'] * $i['qty'],
            $this->items
        )), 2);
    }

    public function getDeliveryDataProperty(): array
    {
        if ($this->cartKind() === 'product') {
            return ['distance_km' => null, 'fee' => $this->productDeliveryFee()];
        }

        if (empty($this->items) || ! Auth::check()) {
            return ['distance_km' => null, 'fee' => $this->fallbackDeliveryFee()];
        }

        $firstItem  = collect($this->items)->first();
        $restaurant = Restaurant::select('id', 'latitude', 'longitude')
            ->find($firstItem['restaurant_id']);

        if (! $restaurant) {
            return ['distance_km' => null, 'fee' => $this->fallbackDeliveryFee()];
        }

        $address = Auth::user()->addresses()->where('is_default', true)->first()
            ?? Auth::user()->addresses()->latest()->first();

        return app(DeliveryChargeService::class)->calculateWithDistance(
            originLat: $restaurant->latitude,
            originLng: $restaurant->longitude,
            destLat: $address?->latitude,
            destLng: $address?->longitude,
            fallbackFee: $this->fallbackDeliveryFee(),
        );
    }

    public function getDeliveryFeeProperty(): float
    {
        return $this->deliveryData['fee'];
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal + (count($this->items) ? $this->deliveryFee : 0);
    }

    public function getCountProperty(): int
    {
        return array_sum(array_column($this->items, 'qty'));
    }

    // ─────────────────────────────────────────
    // COUPON
    // ─────────────────────────────────────────

    private function cartRestaurantId(): ?int
    {
        $first = collect($this->items)->first();

        return $first ? (int) $first['restaurant_id'] : null;
    }

    public function applyCoupon(): void
    {
        $this->couponError = '';

        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);
            return;
        }

        if (empty($this->items)) {
            $this->couponError = 'আগে কার্টে আইটেম যোগ করুন।';
            return;
        }

        if ($this->cartKind() === 'product') {
            $this->couponError = 'প্রোডাক্ট অর্ডারে কুপন প্রযোজ্য নয়।';
            return;
        }

        // Stop people from guessing codes.
        $key = 'coupon-apply:' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->couponError = 'অনেকবার চেষ্টা করেছেন, কিছুক্ষণ পরে আবার চেষ্টা করুন।';
            return;
        }
        RateLimiter::hit($key, 60);

        $deliveryFee = (float) $this->deliveryData['fee'];

        $result = app(CouponService::class)->evaluate(
            $this->couponInput,
            Auth::user(),
            $this->cartRestaurantId(),
            $this->subtotal,
            $deliveryFee
        );

        if ($result['error'] !== null) {
            $this->couponError = $result['error'];
            return;
        }

        $this->couponCode  = $result['coupon']->code;
        $this->couponInput = '';
        $this->dispatch('show-toast', message: '🏷️ কুপন প্রয়োগ হয়েছে!', type: 'success');
    }

    public function removeCoupon(): void
    {
        $this->couponCode  = '';
        $this->couponInput = '';
        $this->couponError = '';
    }

    /**
     * One place that computes every number shown in the cart, so the
     * delivery lookup and the coupon check each run only once per render.
     *
     * @return array{subtotal: float, delivery_fee: float, discount: float, total: float, coupon_error: ?string}
     */
    private function pricing(): array
    {
        $subtotal    = $this->subtotal;
        $deliveryFee = empty($this->items) ? 0.0 : (float) $this->deliveryData['fee'];
        $discount    = 0.0;
        $error       = null;

        if ($this->couponCode !== '' && ! empty($this->items) && Auth::check() && $this->cartKind() !== 'product') {
            $result = app(CouponService::class)->evaluate(
                $this->couponCode,
                Auth::user(),
                $this->cartRestaurantId(),
                $subtotal,
                $deliveryFee
            );

            $discount = $result['discount'];
            $error    = $result['error'];
        }

        return [
            'subtotal'     => $subtotal,
            'delivery_fee' => $deliveryFee,
            'discount'     => $discount,
            'total'        => max(0.0, $subtotal + $deliveryFee - $discount),
            'coupon_error' => $error,
        ];
    }

    #[On('address-saved')]
    public function resumePlaceOrderAfterAddress(): void
    {
        $this->placeOrder();
    }

    public function placeOrder(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);
            return;
        }

        // One checkout at a time per customer: a double click, a retry or a
        // second browser tab cannot create two orders at the same moment.
        $lock = Cache::lock('place-order:' . Auth::id(), 15);

        if (! $lock->get()) {
            $this->dispatch('notify', type: 'error', message: 'আপনার আগের অর্ডারটি প্রসেস হচ্ছে, একটু অপেক্ষা করুন।');
            return;
        }

        try {
            $this->processOrder();
        } finally {
            $lock->release();
        }
    }

    private function processOrder(): void
    {
        if (empty($this->items)) return;

        if ($this->cartKind() === 'product') {
            $this->processProductOrder();
            return;
        }

        $this->processFoodOrder();
    }

    /**
     * Checkout for the admin-product cart.
     * Order::TYPE_ADMIN, restaurant_id = null, OrderItem.orderable_type = Product.
     */
    private function processProductOrder(): void
    {
        $user = Auth::user();

        // Same flow as the food cart: no address -> quick-address modal, and its
        // "address-saved" event resumes placeOrder(). Safe now, one cart only.
        if ($user->addresses()->doesntExist()) {
            $this->dispatch('open-quick-address');
            return;
        }

        $address = $user->addresses()->where('is_default', true)->first()
            ?? $user->addresses()->latest()->first();

        // Re-verify against the DB: never trust session prices/availability.
        $fresh = Product::query()->active()->whereIn('id', array_keys($this->items))->get()->keyBy('id');

        $removed = [];
        foreach ($this->items as $id => $cartItem) {
            if (! $fresh->has($id)) {
                $removed[] = $cartItem['name'];
                unset($this->items[$id]);
            }
        }

        if ($removed !== []) {
            $this->dispatch('notify',
                message: 'কিছু প্রোডাক্ট আর পাওয়া যাচ্ছে না, কার্ট থেকে সরানো হয়েছে: ' . implode(', ', $removed),
                type: 'error'
            );
        }

        if ($this->items === []) {
            return;
        }

        $lines    = [];
        $subtotal = 0.0;

        foreach ($this->items as $id => $cartItem) {
            $product   = $fresh->get($id);
            $qty       = $this->clampQty($cartItem['qty'] ?? 1);
            $price     = (float) $product->price;
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;

            $lines[] = [
                'id'         => $product->id,
                'name'       => $product->name,
                'price'      => $price,
                'qty'        => $qty,
                'image_url'  => $product->image_url,
                'emoji'      => $product->emoji,
                'line_total' => $lineTotal,
            ];
        }

        $deliveryFee = $this->productDeliveryFee();
        $total       = $subtotal + $deliveryFee;

        do {
            $orderNumber = 'KA' . now()->format('ymd') . strtoupper(Str::random(4));
        } while (Order::where('order_number', $orderNumber)->exists());

        try {
            $order = DB::transaction(function () use ($user, $address, $lines, $subtotal, $deliveryFee, $total, $orderNumber) {
                // status / pricing / payment_status are not mass-assignable on
                // Order, so they are set with forceFill() below.
                $order = Order::create([
                    'order_number'               => $orderNumber,
                    'order_type'                 => Order::TYPE_ADMIN,
                    'customer_id'                => $user->id,
                    'restaurant_id'              => null,
                    'rider_id'                   => null,
                    'delivery_address_id'        => $address->id,
                    'delivery_address_snapshot'  => [
                        'label'        => $address->label,
                        'full_address' => $address->full_address,
                        'city'         => $address->city,
                        'postal_code'  => $address->postal_code,
                        'latitude'     => $address->latitude,
                        'longitude'    => $address->longitude,
                    ],
                    'delivery_distance_km'       => null,
                    'coupon_id'                  => null,
                    'payment_method'             => 'cash_on_delivery',
                    'special_instructions'       => null,
                    'estimated_delivery_minutes' => 60,
                    'order_source'               => 'web',
                ]);

                $order->forceFill([
                    'status'                => 'pending',
                    'subtotal'              => $subtotal,
                    'delivery_fee'          => $deliveryFee,
                    'discount_amount'       => 0,
                    'total_amount'          => $total,
                    'payment_status'        => 'pending',
                    'estimated_delivery_at' => now()->addMinutes(60),
                ])->save();

                $rows = array_map(fn (array $l) => [
                    'order_id'        => $order->id,
                    'orderable_type'  => Product::class,
                    'orderable_id'    => $l['id'],
                    'item_image'      => $l['image_url'],
                    'item_name'       => $l['name'],
                    'item_price'      => $l['price'],
                    'quantity'        => $l['qty'],
                    'discount_amount' => 0,
                    'line_total'      => $l['line_total'],
                    'emoji'           => $l['emoji'],
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ], $lines);

                OrderItem::insert($rows);

                OrderStatusLog::create([
                    'order_id'     => $order->id,
                    'from_status'  => null,
                    'to_status'    => 'pending',
                    'changed_by'   => $user->id,
                    'changed_type' => 'customer',
                    'note'         => 'Customer placed product order',
                ]);

                $user->customerProfile()->increment('total_orders');

                activity()
                    ->performedOn($order)
                    ->causedBy($user)
                    ->withProperties([
                        'order_number' => $order->order_number,
                        'order_type'   => Order::TYPE_ADMIN,
                        'total_amount' => $order->total_amount,
                    ])
                    ->log('Product order placed');

                return $order;
            });

            // Transaction is committed here, so it is safe to notify.
            app(OrderNotifier::class)->placed($order);

            $this->clearCart();
            $this->dispatch('order-placed', orderId: $order->id, orderNumber: $order->order_number);
        } catch (\Throwable $e) {
            Log::error('Product order placement failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            // Do not leak internal exception text to the customer.
            $this->dispatch('notify', type: 'error', message: 'অর্ডার দেওয়া যায়নি, আবার চেষ্টা করুন।');
        }
    }

    /** Checkout for a single restaurant's food items. */
    private function processFoodOrder(): void
    {
        if (empty($this->items)) return;

        $user = Auth::user();

        if ($user->addresses()->doesntExist()) {
            $this->dispatch('open-quick-address');
            return;
        }

        // ── Re-verify cart against the DB — never trust stale session prices/availability ──
        $cartItemIds = array_keys($this->items);
        $freshItems  = Food::whereIn('id', $cartItemIds)->get()->keyBy('id');

        $missingOrUnavailable = [];
        foreach ($this->items as $id => $cartItem) {
            $menuItem = $freshItems->get($id);
            if (! $menuItem || ! $menuItem->is_available) {
                $missingOrUnavailable[] = $cartItem['name'];
                unset($this->items[$id]);
            }
        }

        if (! empty($missingOrUnavailable)) {
            $this->dispatch('notify', type: 'error', message:
                'কিছু আইটেম আর পাওয়া যাচ্ছে না এবং কার্ট থেকে সরানো হয়েছে: ' . implode(', ', $missingOrUnavailable)
            );
            if (empty($this->items)) {
                return;
            }
        }

        $firstFreshItem = $freshItems->get(array_key_first($this->items));

        // Every item in one order must belong to the same restaurant,
        // judged by the database, not by what the cart says.
        $restaurantIds = collect($this->items)
            ->keys()
            ->map(fn ($id) => $freshItems->get($id)?->restaurant_id)
            ->unique();

        if ($restaurantIds->count() > 1) {
            $this->items = [];
            $this->dispatch('notify', type: 'error', message: 'একটি অর্ডারে শুধু একটি রেস্টুরেন্টের আইটেম থাকতে পারে। কার্ট খালি করা হয়েছে।');
            return;
        }

        $restaurant = $firstFreshItem
            ? Restaurant::find($firstFreshItem->restaurant_id)
            : null;

        if (! $restaurant || ! $restaurant->is_active || ! $restaurant->is_approved) {
            $this->dispatch('notify', type: 'error', message: 'এই রেস্টুরেন্ট থেকে এখন অর্ডার নেওয়া সম্ভব নয়।');
            return;
        }

        if (! $restaurant->is_open) {
            $this->dispatch('notify', type: 'error', message: 'রেস্টুরেন্টটি এখন বন্ধ, দয়া করে পরে চেষ্টা করুন।');
            return;
        }

        // Rebuild items using the CURRENT DB price, not the stale cart price
        $items = [];
        foreach ($this->items as $id => $cartItem) {
            $menuItem   = $freshItems->get($id);
            $items[$id] = [
                'kind'          => 'food',
                'name'          => $menuItem->name,
                'price'         => $menuItem->price,
                'qty'           => $this->clampQty($cartItem['qty'] ?? 1),
                'image_url'     => $menuItem->image_url,
                'emoji'         => $menuItem->emoji ?? null,
                'restaurant_id' => $menuItem->restaurant_id,
            ];
        }
        $this->items = $items;

        $subtotal       = $this->subtotal;
        $deliveryData   = $this->deliveryData;
        $deliveryFee    = $deliveryData['fee'];
        $deliveryKm     = $deliveryData['distance_km'];

        do {
            $orderNumber = 'KK' . now()->format('ymd') . strtoupper(Str::random(4));
        } while (Order::where('order_number', $orderNumber)->exists());

        $address = $user->addresses()->where('is_default', true)->first()
                   ?? $user->addresses()->latest()->first();

        $restaurantId = (int) $restaurant->id;

        // Applied coupon must still be valid. Never silently charge full price.
        $couponService = app(CouponService::class);

        if ($this->couponCode !== '') {
            $check = $couponService->evaluate($this->couponCode, $user, $restaurantId, $subtotal, $deliveryFee);

            if ($check['error'] !== null) {
                $this->dispatch('notify', type: 'error', message: $check['error']);
                return;
            }
        }
        $couponCode = $this->couponCode;

        try {
            $order = DB::transaction(function () use (
                $user, $items, $subtotal, $deliveryFee, $deliveryKm,
                $orderNumber, $address, $restaurantId, $couponService, $couponCode
            ) {
                // Re-check under a row lock: limits cannot be beaten by two
                // checkouts at the same moment.
                $coupon   = null;
                $discount = 0.0;

                if ($couponCode !== '') {
                    $result = $couponService->evaluate($couponCode, $user, $restaurantId, $subtotal, $deliveryFee, lock: true);

                    if ($result['error'] !== null) {
                        throw new \DomainException($result['error']);
                    }

                    $coupon   = $result['coupon'];
                    $discount = $result['discount'];
                }

                $total = max(0.0, $subtotal + $deliveryFee - $discount);

                // NOTE: Order's $fillable does not include status, subtotal,
                // delivery_fee, discount_amount, total_amount, payment_status
                // or estimated_delivery_at (they're server-calculated columns
                // per the model's own comment), so those are set via
                // forceFill() below rather than passed into create().
                $order = Order::create([
                    'order_number'               => $orderNumber,
                    'order_type'                 => Order::TYPE_VENDOR,
                    'customer_id'                => $user->id,
                    'restaurant_id'              => $restaurantId,
                    'rider_id'                   => null,
                    'delivery_address_id'        => $address?->id,
                    'delivery_address_snapshot'  => $address ? [
                        'label'        => $address->label,
                        'full_address' => $address->full_address,
                        'city'         => $address->city,
                        'postal_code'  => $address->postal_code,
                        'latitude'     => $address->latitude,
                        'longitude'    => $address->longitude,
                    ] : null,
                    'delivery_distance_km'       => $deliveryKm,
                    'coupon_id'                  => $coupon?->id,
                    'payment_method'             => 'cash_on_delivery',
                    'special_instructions'       => null,
                    'estimated_delivery_minutes' => 45,
                    'order_source'               => 'web',
                ]);

                $order->forceFill([
                    'status'                => 'pending',
                    'subtotal'              => $subtotal,
                    'delivery_fee'          => $deliveryFee,
                    'discount_amount'       => $discount,
                    'total_amount'          => $total,
                    'payment_status'        => 'pending',
                    'estimated_delivery_at' => now()->addMinutes(45),
                ])->save();

                $orderItems = [];
                foreach ($items as $id => $item) {
                    $orderItems[] = [
                        'order_id'        => $order->id,
                        'orderable_type'  => Food::class,
                        'orderable_id'    => $id,
                        'item_image'      => $item['image_url'] ?? null,
                        'item_name'       => $item['name'],
                        'item_price'      => $item['price'],
                        'quantity'        => $item['qty'],
                        'discount_amount' => 0,
                        'line_total'      => $item['price'] * $item['qty'],
                        'emoji'           => $item['emoji'] ?? null,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }

                OrderItem::insert($orderItems);

                if ($coupon) {
                    $couponService->redeem($coupon, $order, $user, $discount);
                }

                OrderStatusLog::create([
                    'order_id'    => $order->id,
                    'from_status' => null,
                    'to_status'   => 'pending',
                    'changed_by'  => $user->id,
                    'changed_type' => 'customer',
                    'note'        => 'Customer placed order',
                ]);

                $user->customerProfile()->increment('total_orders');

                activity()
                    ->performedOn($order)
                    ->causedBy($user)
                    ->withProperties([
                        'order_number'         => $order->order_number,
                        'total_amount'         => $order->total_amount,
                        'delivery_fee'         => $order->delivery_fee,
                        'discount_amount'      => $order->discount_amount,
                        'coupon_code'          => $coupon?->code,
                        'delivery_distance_km' => $order->delivery_distance_km,
                    ])
                    ->log('Order placed');

                return $order;
            });

            // Transaction is committed here, so it is safe to notify.
            app(OrderNotifier::class)->placed($order);

            $this->clearCart();
            $this->dispatch('order-placed', orderId: $order->id, orderNumber: $order->order_number);

        } catch (\DomainException $e) {
            // Coupon became invalid between the preview and checkout.
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Order placement failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            // Internal error text must not be shown to the customer.
            $this->dispatch('notify', type: 'error', message: 'অর্ডার দেওয়া যায়নি, আবার চেষ্টা করুন।');
        }
    }

    public function render()
    {
        $kind        = $this->cartKind();
        $pendingKind = $this->pendingItem['kind'] ?? 'food';

        return view('livewire.customer.cart-component', [
            'items'         => $this->items,
            'pricing'       => $this->pricing(),
            'kind'          => $kind ?? 'food',
            // Product <-> restaurant conflicts say "ধরনের", restaurant <-> restaurant says "রেস্তোরাঁর".
            'conflictMixed' => $this->showConflict && ($pendingKind === 'product' || $kind === 'product'),
        ]);
    }
}