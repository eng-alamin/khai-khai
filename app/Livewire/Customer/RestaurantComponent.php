<?php
// app/Livewire/Customer/RestaurantComponent.php

namespace App\Livewire\Customer;

use App\Models\Restaurant;
use Livewire\Component;

class RestaurantComponent extends Component
{
    public ?string $activeCategory = null; // null = সব

    public function setCategory(?string $category): void
    {
        $this->activeCategory = $category;
    }

    public function render()
    {
        // সব active+approved restaurant-এর distinct category
        $categories = Restaurant::query()
            ->where('is_active', true)
            ->where('is_approved', true)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $restaurantsQuery = Restaurant::query()
            ->where('is_active', true)
            ->where('is_approved', true)
            ->orderByDesc('avg_rating');

        if ($this->activeCategory !== null) {
            $restaurantsQuery->where('category', $this->activeCategory);
        }

        $filteredRestaurants = $restaurantsQuery->get([
            'id', 'name', 'slug', 'category', 'emoji',
            'logo_url', 'banner_url', 'city',
            'avg_delivery_min', 'avg_delivery_max',
            'delivery_fee', 'avg_rating', 'total_reviews',
            'tag', 'is_open',
        ]);

        return view('livewire.customer.restaurant-component', [
                'categories'           => $categories,
                'filteredRestaurants'  => $filteredRestaurants,
            ])
            ->layout('layouts.customer', [
                'title'           => 'Restaurants | KhaiKhai',
                'breadcrumbTitle' => 'রেস্তোরাঁ',
            ]);
    }
}