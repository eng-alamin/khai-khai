<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\Product;

class HomeComponent extends Component
{
    public string $searchQuery = '';

    public function searchFood(): void
    {
        $this->redirectRoute('customer.restaurants', ['search' => $this->searchQuery]);
    }

    public function filterByCategory(?int $categoryId = null): void
    {
        $this->redirectRoute('customer.items', $categoryId ? ['category' => $categoryId] : []);
    }

    public function render()
    {
        $restaurants = Restaurant::query()
            ->where('is_active', true)
            ->where('is_approved', true)
            ->orderByDesc('avg_rating')
            ->limit(6)
            ->get([
                'id', 'name', 'slug', 'category', 'emoji',
                'logo_url', 'banner_url', 'city', 'avg_rating', 'total_reviews',
                'tag', 'is_open',
            ]);

        $menuItems = MenuItem::query()
            ->where('is_available', true)
            ->whereHas('restaurant', fn ($q) =>
                $q->where('is_active', true)->where('is_approved', true)
            )
            ->with('category:id,name,emoji', 'restaurant:id,name')
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->limit(4)
            ->get([
                'id', 'category_id', 'name', 'slug', 'price',
                'compare_price', 'emoji', 'image_url', 'section', 'sort_order',
            ]);

        $categories = MenuCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'emoji']);

        return view('livewire.customer.home-component', [
                'restaurants' => $restaurants,
                'menuItems'   => $menuItems,
                'products'    => $products,
                'categories'  => $categories,
            ])
            ->layout('layouts.customer', [
                'title'           => 'Home | KhaiKhai',
                'breadcrumbTitle' => 'Home',
            ]);
    }
}