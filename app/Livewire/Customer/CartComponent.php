<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use App\Models\Food;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Restaurant;
use App\Services\DeliveryChargeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CartComponent extends Component
{
    #[Session(key: 'kk_cart')]
    public array $items = [];

    public bool  $open         = false;
    public bool  $showConflict = false;
    public array $pendingItem  = [];

    public float $delivery = 49.00;


    #[On('add-to-cart')]
    public function addItem(int $id, string $name, float $price): void
    {
        if (isset($this->items[$id])) {
            $this->items[$id]['qty']++;
            return;
        }

        $menuItem = Food::find($id);
        if (! $menuItem) return;

        if (! empty($this->items)) {
            $currentRestaurantId = collect($this->items)->first()['restaurant_id'];
            if ($currentRestaurantId !== $menuItem->restaurant_id) {
                $this->pendingItem = [
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
        }

        $this->doAddItem($menuItem);
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
        if (! empty($this->items) && collect($this->items)->first()['restaurant_id'] !== $restaurantId) {
            $this->items = [];
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

    public function confirmClearAndAdd(): void
    {
        $this->items        = [];
        $this->open         = false;
        $this->showConflict = false;

        if (! empty($this->pendingItem)) {
            $menuItem = Food::find($this->pendingItem['id']);
            if ($menuItem) {
                $this->doAddItem($menuItem);
            }
            $this->pendingItem = [];
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
            $this->items[$menuItem->id]['qty'] += $qty;
            return;
        }

        $this->items[$menuItem->id] = [
            'name'          => $menuItem->name,
            'price'         => $menuItem->price,
            'qty'           => $qty,
            'image_url'     => $menuItem->image_url,
            'emoji'         => $menuItem->emoji ?? null,
            'restaurant_id' => $menuItem->restaurant_id,
        ];
    }

    public function increment(int $id): void
    {
        if (isset($this->items[$id])) {
            $this->items[$id]['qty']++;
        }
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
        return array_sum(array_map(
            fn($i) => $i['price'] * $i['qty'],
            $this->items
        ));
    }

    public function getDeliveryDataProperty(): array
    {
        if (empty($this->items) || ! Auth::check()) {
            return ['distance_km' => null, 'fee' => $this->delivery];
        }

        $firstItem  = collect($this->items)->first();
        $restaurant = Restaurant::select('id', 'latitude', 'longitude')
            ->find($firstItem['restaurant_id']);

        if (! $restaurant) {
            return ['distance_km' => null, 'fee' => $this->delivery];
        }

        $address = Auth::user()->addresses()->where('is_default', true)->first()
            ?? Auth::user()->addresses()->latest()->first();

        return app(DeliveryChargeService::class)->calculateWithDistance(
            originLat: $restaurant->latitude,
            originLng: $restaurant->longitude,
            destLat: $address?->latitude,
            destLng: $address?->longitude,
            fallbackFee: $this->delivery,
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
        $restaurant     = $firstFreshItem
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
                'name'          => $menuItem->name,
                'price'         => $menuItem->price,
                'qty'           => $cartItem['qty'],
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
        $total          = $subtotal + $deliveryFee;

        do {
            $orderNumber = 'KK' . now()->format('ymd') . strtoupper(Str::random(4));
        } while (Order::where('order_number', $orderNumber)->exists());

        $address = $user->addresses()->where('is_default', true)->first()
                   ?? $user->addresses()->latest()->first();

        $firstItemId  = array_key_first($items);
        $restaurantId = Food::find($firstItemId)?->restaurant_id;

        try {
            $order = DB::transaction(function () use (
                $user, $items, $subtotal, $deliveryFee, $deliveryKm, $total,
                $orderNumber, $address, $restaurantId
            ) {
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
                    'coupon_id'                  => null,
                    'payment_method'             => 'cash_on_delivery',
                    'special_instructions'       => null,
                    'estimated_delivery_minutes' => 45,
                    'order_source'               => 'web',
                ]);

                $order->forceFill([
                    'status'                => 'pending',
                    'subtotal'              => $subtotal,
                    'delivery_fee'          => $deliveryFee,
                    'discount_amount'       => 0,
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

                OrderStatusLog::create([
                    'order_id'    => $order->id,
                    'from_status' => null,
                    'to_status'   => 'pending',
                    'changed_by'  => $user->id,
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
                        'delivery_distance_km' => $order->delivery_distance_km,
                    ])
                    ->log('Order placed');

                return $order;
            });

            $this->clearCart();
            $this->dispatch('order-placed', orderId: $order->id, orderNumber: $order->order_number);

        } catch (\Throwable $e) {
            Log::error('Order placement failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.customer.cart-component', [
            'items' => $this->items,
        ]);
    }
}