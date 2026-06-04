<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\MenuItem;
use App\Models\Restaurant;

class HomeComponent extends Component
{
    public string $searchQuery = '';

    public function searchFood(): void
    {
        $this->redirectRoute('customer.restaurants', ['search' => $this->searchQuery]);
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
                'logo_url', 'banner_url', 'city',
                'avg_delivery_min', 'avg_delivery_max',
                'delivery_fee', 'avg_rating', 'total_reviews',
                'tag', 'is_open',
            ]);

        $menuItems = MenuItem::query()
            ->where('is_available', true)
            ->with('category:id,name,emoji', 'restaurant:id,name')
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        return view('livewire.customer.home-component', [
                'restaurants' => $restaurants,
                'menuItems'   => $menuItems,
            ])
            ->layout('layouts.customer', [
                'title'           => 'Home | KhaiKhai',
                'breadcrumbTitle' => 'Home',
            ]);
    }
}