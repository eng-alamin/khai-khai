<?php

declare(strict_types=1);

namespace App\Livewire\Customer;

use App\Models\AdminSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Product;
use App\Services\OrderNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

/**
 * Separate cart + checkout for ADMIN products (Product model).
 *
 * Vendor food is handled by CartComponent (Food model) and is NOT touched.
 * Orders created here use Order::TYPE_ADMIN with restaurant_id = null and
 * OrderItem.orderable_type = Product::class.
 */
class ProductCartComponent extends Component
{
    private const MAX_QTY_PER_ITEM = 20;

    /** @var array<int, array{name:string, price:float, qty:int, image_url:?string, emoji:?string}> */
    #[Session(key: 'kk_product_cart')]
    public array $items = [];

    public bool $open = false;

    /** Flat delivery fee (Taka) for admin product orders. */
    private function flatDeliveryFee(): float
    {
        return (float) AdminSetting::get('product_delivery_fee', 49);
    }

    // ─────────────────────────────────────────
    // ADD / REORDER
    // ─────────────────────────────────────────

    /**
     * Only the product ID is trusted from the browser. Name and price are
     * always read fresh from the database.
     */
    #[On('add-product-to-cart')]
    public function addItem(int $id): void
    {
        $product = Product::query()->active()->find($id);

        if (! $product) {
            $this->dispatch('notify', message: 'এই প্রোডাক্টটি আর পাওয়া যাচ্ছে না।', type: 'error');
            return;
        }

        $this->pushItem($product, 1);
        $this->dispatch('notify', message: '🛒 কার্টে যোগ হয়েছে!', type: 'success');
    }

    /**
     * Receives [{id, qty}, ...] from OrderListComponent::reorder().
     * Price and availability are always re-read from the database.
     */
    #[On('reorder-products')]
    public function mergeReorderedItems(array $items): void
    {
        $ids = array_map('intval', array_column($items, 'id'));

        $products = Product::query()->active()->whereIn('id', $ids)->get()->keyBy('id');

        if ($products->isEmpty()) {
            $this->dispatch('notify', message: 'এই প্রোডাক্টগুলো আর পাওয়া যাচ্ছে না।', type: 'error');
            return;
        }

        $skipped = 0;

        foreach ($items as $row) {
            $product = $products->get((int) ($row['id'] ?? 0));

            if (! $product) {
                $skipped++;
                continue;
            }

            $this->pushItem($product, max(1, (int) ($row['qty'] ?? 1)));
        }

        $this->open = true;

        $message = $skipped > 0
            ? "কিছু প্রোডাক্ট পাওয়া যায়নি ({$skipped}টি বাদ গেছে)।"
            : '🛒 প্রোডাক্ট কার্টে যোগ হয়েছে!';

        $this->dispatch('notify', message: $message, type: $skipped > 0 ? 'info' : 'success');
    }

    private function pushItem(Product $product, int $qty): void
    {
        $id = $product->id;

        if (isset($this->items[$id])) {
            $this->items[$id]['qty'] = min(self::MAX_QTY_PER_ITEM, $this->items[$id]['qty'] + $qty);
            return;
        }

        $this->items[$id] = [
            'name'      => $product->name,
            'price'     => (float) $product->price,
            'qty'       => min(self::MAX_QTY_PER_ITEM, $qty),
            'image_url' => $product->image_url,
            'emoji'     => $product->emoji,
        ];
    }

    // ─────────────────────────────────────────
    // CART ACTIONS
    // ─────────────────────────────────────────

    public function increment(int $id): void
    {
        if (isset($this->items[$id])) {
            $this->items[$id]['qty'] = min(self::MAX_QTY_PER_ITEM, $this->items[$id]['qty'] + 1);
        }
    }

    public function decrement(int $id): void
    {
        if (! isset($this->items[$id])) {
            return;
        }

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
        $this->items = [];
        $this->open  = false;
    }

    public function toggleCart(): void
    {
        $this->open = ! $this->open;
    }

    // ─────────────────────────────────────────
    // TOTALS
    // ─────────────────────────────────────────

    public function getSubtotalProperty(): float
    {
        return array_sum(array_map(
            fn (array $i) => $i['price'] * $i['qty'],
            $this->items
        ));
    }

    public function getDeliveryFeeProperty(): float
    {
        return $this->items === [] ? 0.0 : $this->flatDeliveryFee();
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal + $this->deliveryFee;
    }

    public function getCountProperty(): int
    {
        return (int) array_sum(array_column($this->items, 'qty'));
    }

    // ─────────────────────────────────────────
    // CHECKOUT
    // ─────────────────────────────────────────

    public function placeOrder(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);
            return;
        }

        // One checkout at a time per customer (same key as the food cart):
        // a double click, a retry or a second browser tab cannot create
        // two orders at the same moment.
        $lock = Cache::lock('place-order:' . Auth::id(), 15);

        if (! $lock->get()) {
            $this->dispatch('notify', message: 'আপনার আগের অর্ডারটি প্রসেস হচ্ছে, একটু অপেক্ষা করুন।', type: 'error');
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
        if ($this->items === []) {
            return;
        }

        $user = Auth::user();

        $address = $user->addresses()->where('is_default', true)->first()
            ?? $user->addresses()->latest()->first();

        if (! $address) {
            // Redirect to the address page instead of the quick-address modal,
            // because that modal's "address-saved" event also resumes the
            // vendor cart and could place two orders at once.
            session()->flash('info', 'অর্ডার করতে আগে একটি ডেলিভারি ঠিকানা যোগ করুন।');
            $this->redirect(route('customer.addresses'), navigate: true);
            return;
        }

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
            $qty       = max(1, min(self::MAX_QTY_PER_ITEM, (int) $cartItem['qty']));
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

        $deliveryFee = $this->flatDeliveryFee();
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
                    'order_id'    => $order->id,
                    'from_status' => null,
                    'to_status'   => 'pending',
                    'changed_by'  => $user->id,
                    'changed_type' => 'customer',
                    'note'        => 'Customer placed product order',
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
            $this->dispatch('notify', message: 'অর্ডার দেওয়া যায়নি, আবার চেষ্টা করুন।', type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.customer.product-cart-component', [
            'items' => $this->items,
        ]);
    }
}
