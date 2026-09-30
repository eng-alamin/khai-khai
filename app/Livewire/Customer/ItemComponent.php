<?php
// app/Livewire/Customer/ItemComponent.php

namespace App\Livewire\Customer;

use App\Models\Category;
use App\Models\Food;
use Livewire\Component;
use Livewire\WithPagination;

class ItemComponent extends Component
{
    use WithPagination;

    public ?string $activeCategory = null; // null = সব, otherwise category name

    public function setCategory(?string $categoryName): void
    {
        $this->activeCategory = $categoryName;
        $this->resetPage();
    }

    public function render()
    {
        // সব restaurant-এর distinct category name + emoji
        $categories = Category::where('type', Category::TYPE_FOOD)
            ->where('is_active', true)
            ->select('name', 'emoji')
            ->distinct()
            ->orderBy('name')
            ->get();

        $itemsQuery = Food::query()
            ->where('is_available', true)
            ->whereHas('restaurant', fn ($q) =>
                $q->where('is_active', true)->where('is_approved', true)
            )
            ->with('category:id,name,emoji', 'restaurant:id,name,slug')
            ->orderBy('sort_order');

        if ($this->activeCategory !== null) {
            $itemsQuery->whereHas('category', function ($q) {
                $q->where('name', $this->activeCategory);
            });
        }

        return view('livewire.customer.item-component', [
                'categories'    => $categories,
                'filteredItems' => $itemsQuery->paginate(20),
            ])
            ->layout('layouts.customer', [
                'title'           => 'Menu | KhaiKhai',
                'breadcrumbTitle' => 'Food Menu',
            ]);
    }
}