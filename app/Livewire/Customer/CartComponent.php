<?php
// app/Livewire/Customer/CartComponent.php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CartComponent extends Component
{
    // ✅ #[Session] — Livewire নিজেই session-এ save/restore করবে
    // page refresh করলেও items থাকবে
    #[Session(key: 'kk_cart')]
    public array $items = [];

    public bool  $open         = false;
    public int   $delivery     = 4900;
    public bool  $showConflict = false;
    public array $pendingItem  = [];

    // ─────────────────────────────────────────
    // ADD TO CART
    // ─────────────────────────────────────────
    #[On('add-to-cart')]
    public function addItem(int $id, string $name, int $price): void
    {
        // ইতিমধ্যে আছে → qty বাড়াও
        if (isset($this->items[$id])) {
            $this->items[$id]['qty']++;
            return;
        }

        $menuItem = MenuItem::find($id);
        if (! $menuItem) return;

        // আলাদা restaurant → conflict modal
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

    // Conflict — "হ্যাঁ, খালি করুন"
    public function confirmClearAndAdd(): void
    {
        $this->items        = [];
        $this->open         = false;
        $this->showConflict = false;

        if (! empty($this->pendingItem)) {
            $menuItem = MenuItem::find($this->pendingItem['id']);
            if ($menuItem) {
                $this->doAddItem($menuItem);
            }
            $this->pendingItem = [];
        }
    }

    // Conflict — "না, রাখুন"
    public function cancelConflict(): void
    {
        $this->showConflict = false;
        $this->pendingItem  = [];
    }

    // ─────────────────────────────────────────
    // INTERNAL HELPER
    // ─────────────────────────────────────────
    private function doAddItem(MenuItem $menuItem): void
    {
        $this->items[$menuItem->id] = [
            'name'          => $menuItem->name,
            'price'         => $menuItem->price,
            'qty'           => 1,
            'image_url'     => $menuItem->image_url,
            'emoji'         => $menuItem->emoji ?? null,
            'restaurant_id' => $menuItem->restaurant_id,
        ];
    }

    // ─────────────────────────────────────────
    // QTY CONTROLS
    // ─────────────────────────────────────────
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

    // ─────────────────────────────────────────
    // COMPUTED PROPERTIES
    // ─────────────────────────────────────────
    public function getSubtotalProperty(): int
    {
        return array_sum(array_map(
            fn($i) => $i['price'] * $i['qty'],
            $this->items
        ));
    }

    public function getTotalProperty(): int
    {
        return $this->subtotal + (count($this->items) ? $this->delivery : 0);
    }

    public function getCountProperty(): int
    {
        return array_sum(array_column($this->items, 'qty'));
    }

    // ─────────────────────────────────────────
    // PLACE ORDER
    // ─────────────────────────────────────────
    public function placeOrder(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);
            return;
        }

        if (empty($this->items)) return;

        $user     = Auth::user();
        $items    = $this->items;
        $subtotal = $this->subtotal;
        $delivery = $this->delivery;
        $total    = $this->total;

        // ✅ Unique order number নিশ্চিত করো
        do {
            $orderNumber = 'KK' . now()->format('ymd') . strtoupper(Str::random(4));
        } while (Order::where('order_number', $orderNumber)->exists());

        $address = $user->addresses()->where('is_default', true)->first()
                   ?? $user->addresses()->latest()->first();

        $firstItemId  = array_key_first($items);
        $restaurantId = MenuItem::find($firstItemId)?->restaurant_id;

        try {
            $order = DB::transaction(function () use (
                $user, $items, $subtotal, $delivery, $total,
                $orderNumber, $address, $restaurantId
            ) {
                $order = Order::create([
                    'order_number'              => $orderNumber,
                    'customer_id'               => $user->id,
                    'restaurant_id'             => $restaurantId,
                    'rider_id'                  => null,
                    'delivery_address_id'       => $address?->id,
                    // ✅ address না থাকলে null — column nullable() করতে হবে
                    'delivery_address_snapshot' => $address ? [
                        'label'        => $address->label,
                        'full_address' => $address->full_address,
                        'city'         => $address->city,
                        'postal_code'  => $address->postal_code,
                        'latitude'     => $address->latitude,
                        'longitude'    => $address->longitude,
                    ] : null,
                    'status'                => 'pending',
                    'subtotal'              => $subtotal,
                    'delivery_fee'          => $delivery,
                    'discount_amount'       => 0,
                    'total_amount'          => $total,
                    'coupon_id'             => null,
                    'payment_method'        => 'cash_on_delivery',
                    'payment_status'        => 'pending',
                    'special_instructions'  => null,
                    'estimated_delivery_at' => now()->addMinutes(45),
                ]);

                $orderItems = [];
                foreach ($items as $id => $item) {
                    $orderItems[] = [
                        'order_id'     => $order->id,
                        'menu_item_id' => $id,
                        'item_name'    => $item['name'],
                        'item_price'   => $item['price'],
                        'quantity'     => $item['qty'],
                        'line_total'   => $item['price'] * $item['qty'],
                        'emoji'        => $item['emoji'] ?? null,
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

    // ─────────────────────────────────────────
    // RENDER
    // ─────────────────────────────────────────
    public function render()
    {
        return view('livewire.customer.cart-component', [
            'items' => $this->items,
        ]);
    }
}