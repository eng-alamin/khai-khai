<?php
// app/Livewire/Customer/RestaurantDetailComponent.php

namespace App\Livewire\Customer;

use App\Models\Food;
use App\Models\Restaurant;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Ekta restaurant-er detail page: restaurant info + tar sob available item.
 * Route: restaurants/{slug}  (name: customer.restaurant)
 */
class RestaurantDetailComponent extends Component
{
    /** Locked: set only in mount() from the slug, never from the browser. */
    #[Locked]
    public int $restaurantId;

    public function mount(string $slug): void
    {
        $restaurant = Restaurant::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('is_approved', true)
            ->firstOrFail();

        $this->restaurantId = $restaurant->id;
    }

    public function render()
    {
        $restaurant = Restaurant::query()
            ->where('is_active', true)
            ->where('is_approved', true)
            ->findOrFail($this->restaurantId);

        $foods = Food::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->with('category:id,name,emoji', 'restaurant:id,name,slug', 'menuSection:id,name,sort_order,is_active')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Prothome restaurant er nijer menu section (sort_order onujayi), tarpor
        // jegulor section nai (ba section inactive) segulo category onujayi.
        $sectionGroups  = [];
        $categoryGroups = [];

        foreach ($foods as $food) {
            $section = $food->menuSection;

            if ($section && $section->is_active) {
                $sectionGroups[$section->id] ??= [
                    'title' => $section->name,
                    'emoji' => null,
                    'order' => $section->sort_order,
                    'id'    => $section->id,
                    'items' => collect(),
                ];
                $sectionGroups[$section->id]['items']->push($food);

                continue;
            }

            $categoryKey = $food->category_id ?? 0;

            $categoryGroups[$categoryKey] ??= [
                'title' => $food->category?->name ?? 'অন্যান্য',
                'emoji' => $food->category?->emoji,
                'order' => 0,
                'id'    => $categoryKey,
                'items' => collect(),
            ];
            $categoryGroups[$categoryKey]['items']->push($food);
        }

        $groups = collect($sectionGroups)
            ->sortBy([['order', 'asc'], ['id', 'asc']])
            ->values()
            ->concat(array_values($categoryGroups));

        return view('livewire.customer.restaurant-detail-component', [
                'restaurant' => $restaurant,
                'groups'     => $groups,
                'totalItems' => $foods->count(),
            ])
            ->layout('layouts.customer', [
                'title'           => $restaurant->name . ' | KhaiKhai',
                'breadcrumbTitle' => 'Restaurant',
            ]);
    }
}
