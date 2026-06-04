<?php
// app/Livewire/Customer/ItemComponent.php

namespace App\Livewire\Customer;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Livewire\Component;

class ItemComponent extends Component
{
    public ?string $activeCategory = null; // null = সব, otherwise category name

    public function setCategory(?string $categoryName): void
    {
        $this->activeCategory = $categoryName;
    }

    public function addToCart(int $itemId): void
    {
        $item = MenuItem::find($itemId);
        if (! $item) return;

        $this->dispatch('add-to-cart',
            id:    $item->id,
            name:  $item->name,
            price: $item->price,
        );
    }

    public function render()
    {
        // সব restaurant-এর distinct category name + emoji
        // same নামের category একবারই দেখাবে
        $categories = MenuCategory::where('is_active', true)
            ->select('name', 'emoji')
            ->distinct()          // duplicate name বাদ
            ->orderBy('name')
            ->get();

        $itemsQuery = MenuItem::query()
            ->where('is_available', true)
            ->with('category:id,name,emoji', 'restaurant:id,name')
            ->orderBy('sort_order');

        if ($this->activeCategory !== null) {
            // category name দিয়ে filter — সব restaurant-এর ওই নামের category
            $itemsQuery->whereHas('category', function ($q) {
                $q->where('name', $this->activeCategory);
            });
        }

        $filteredItems = $itemsQuery->get();

        return view('livewire.customer.item-component', [
                'categories'    => $categories,
                'filteredItems' => $filteredItems,
            ])
            ->layout('layouts.customer', [
                'title'           => 'Menu | KhaiKhai',
                'breadcrumbTitle' => 'Menu',
            ]);
    }
}