<?php

namespace App\Livewire\Customer;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Models\Category;
use App\Models\Food;
use App\Models\Restaurant;
use App\Models\Product;
use App\Models\Slider;

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

    // ── Delivery location for the hero bar ─────────────────
    // ASSUMPTION: Auth::user()->addresses() relation on the User model,
    // returning rows from `customer_addresses` (label, full_address, city,
    // postal_code — confirmed columns). Ordered by latest so a freshly
    // added address surfaces immediately. If the relation name differs in
    // your codebase, this is the only place to adjust.
    private function currentAddress()
    {
        if (! Auth::check()) {
            return null;
        }

        return Auth::user()->addresses()->latest()->first();
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

        $menuItems = Food::query()
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

        $categories = Category::query()
            ->where('type', Category::TYPE_FOOD)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'emoji']);

        $sliders = Slider::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get(['id', 'image', 'url']);

        return view('livewire.customer.home-component', [
                'restaurants'     => $restaurants,
                'menuItems'       => $menuItems,
                'products'        => $products,
                'categories'      => $categories,
                'sliders'         => $sliders,
                'currentAddress'  => $this->currentAddress(),
            ])
            ->layout('layouts.customer', [
                'title'           => 'Home | KhaiKhai',
                'breadcrumbTitle' => 'Home',
            ]);
    }
}