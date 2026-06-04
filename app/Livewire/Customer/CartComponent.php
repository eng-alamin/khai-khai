<?php
// app/Livewire/Customer/CartComponent.php

namespace App\Livewire\Customer;

use App\Models\MenuItem;
use Livewire\Component;
use Livewire\Attributes\On;

class CartComponent extends Component
{
    public bool   $open       = false;
    public array  $items      = [];   // [item_id => ['name','price','qty','image_url']]
    public int    $delivery   = 4900; // paisa — ৳49

    // ItemComponent থেকে event আসলে
    #[On('add-to-cart')]
    public function addItem(int $id, string $name, int $price): void
    {
        if (isset($this->items[$id])) {
            $this->items[$id]['qty']++;
        } else {
            $item = MenuItem::find($id, ['id', 'name', 'price', 'image_url']);
            $this->items[$id] = [
                'name'      => $item->name,
                'price'     => $item->price,   // paisa
                'qty'       => 1,
                'image_url' => $item->image_url,
            ];
        }
        
        $this->dispatch('cart-updated');
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
        $this->items = array_values($this->items) // index reset — কিন্তু আমরা id-keyed রাখব
            ? $this->items : [];
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

    // subtotal — paisa
    public function getSubtotalProperty(): int
    {
        return array_sum(array_map(
            fn($i) => $i['price'] * $i['qty'],
            $this->items
        ));
    }

    // total — paisa
    public function getTotalProperty(): int
    {
        return $this->subtotal + (count($this->items) ? $this->delivery : 0);
    }

    public function getCountProperty(): int
    {
        return array_sum(array_column($this->items, 'qty'));
    }

    public function placeOrder(): void
    {
        if (empty($this->items)) return;

        // TODO: Order model-এ save করো
        // Order::create([...]);

        $this->clearCart();
        $this->dispatch('order-placed');
    }

    public function render()
    {
        return view('livewire.customer.cart-component');
    }
}