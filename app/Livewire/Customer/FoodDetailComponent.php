<?php
// app/Livewire/Customer/FoodDetailComponent.php

namespace App\Livewire\Customer;

use App\Models\Food;
use Livewire\Component;

/**
 * Ekta food item-er detail page.
 * Route: food/{id}  (name: customer.food)
 * Add to cart: CartComponent-er 'add-to-cart' event e jay
 * (single-restaurant rule CartComponent-i enforce kore).
 */
class FoodDetailComponent extends Component
{
    public int $foodId;

    public function mount(int $id): void
    {
        $food = Food::query()
            ->whereHas('restaurant', fn ($q) =>
                $q->where('is_active', true)->where('is_approved', true)
            )
            ->findOrFail($id);

        $this->foodId = $food->id;
    }

    public function render()
    {
        $food = Food::query()
            ->whereHas('restaurant', fn ($q) =>
                $q->where('is_active', true)->where('is_approved', true)
            )
            ->with('category:id,name,emoji', 'restaurant:id,name,slug,emoji,logo_url,city,is_open,avg_rating,total_reviews')
            ->findOrFail($this->foodId);

        // Ei restaurant-er aro kichu item
        $moreItems = Food::query()
            ->where('restaurant_id', $food->restaurant_id)
            ->where('is_available', true)
            ->where('id', '!=', $food->id)
            ->with('category:id,name,emoji', 'restaurant:id,name,slug')
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        return view('livewire.customer.food-detail-component', [
                'food'      => $food,
                'moreItems' => $moreItems,
            ])
            ->layout('layouts.customer', [
                'title'           => $food->name . ' | KhaiKhai',
                'breadcrumbTitle' => 'Food Details',
            ]);
    }
}
