<?php
// app/Livewire/Customer/RestaurantComponent.php

namespace App\Livewire\Customer;

use App\Models\Restaurant;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class RestaurantComponent extends Component
{
    use WithPagination;

    public ?string $activeCategory = null; // null = সব

    #[Url(as: 'search', keep: true)]
    public string $search = '';

    public function setCategory(?string $category): void
    {
        $this->activeCategory = $category;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
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
            ->when($this->search !== '', fn ($q) =>
                $q->where(fn ($q2) =>
                    $q2->where('name', 'like', "%{$this->search}%")
                       ->orWhere('category', 'like', "%{$this->search}%")
                )
            )
            ->orderByDesc('avg_rating');

        if ($this->activeCategory !== null) {
            $restaurantsQuery->where('category', $this->activeCategory);
        }

        $filteredRestaurants = $restaurantsQuery->paginate(20, [
            'id', 'name', 'slug', 'category', 'emoji',
            'logo_url', 'banner_url', 'city',
            'avg_rating', 'total_reviews',
            'tag', 'is_open',
        ]);

        return view('livewire.customer.restaurant-component', [
                'categories'           => $categories,
                'filteredRestaurants'  => $filteredRestaurants,
            ])
            ->layout('layouts.customer', [
                'title'           => 'Restaurants | KhaiKhai',
                'breadcrumbTitle' => 'Restaurants',
            ]);
    }
}