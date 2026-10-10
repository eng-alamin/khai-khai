<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FoodResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\RestaurantResource;
use App\Http\Resources\SliderResource;
use App\Models\Category;
use App\Models\Food;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Slider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only catalog for the customer app. The queries mirror the Livewire
 * components (HomeComponent, RestaurantComponent, ItemComponent) so the app
 * and the website always show the same restaurants and food.
 */
class CustomerCatalogController extends Controller
{
    private const RESTAURANT_COLUMNS = [
        'id', 'name', 'slug', 'category', 'emoji',
        'logo_url', 'banner_url', 'city', 'avg_rating', 'total_reviews',
        'tag', 'is_open',
    ];

    /** GET /customer/home */
    public function home(): JsonResponse
    {
        $restaurants = $this->visibleRestaurants()
            ->orderByDesc('avg_rating')
            ->limit(6)
            ->get(self::RESTAURANT_COLUMNS);

        $menuItems = $this->visibleFoods()
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

        return response()->json([
            'data' => [
                'restaurants' => RestaurantResource::collection($restaurants)->resolve(),
                'menu_items'  => FoodResource::collection($menuItems)->resolve(),
                'products'    => ProductResource::collection($products)->resolve(),
                'categories'  => $categories->map(fn (Category $c) => [
                    'id'    => $c->id,
                    'name'  => $c->name,
                    'emoji' => $c->emoji,
                ])->all(),
                'sliders'     => SliderResource::collection($sliders)->resolve(),
            ],
        ]);
    }

    /** GET /customer/restaurants?search=&category=&page= */
    public function restaurants(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search'   => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $search   = trim((string) ($validated['search'] ?? ''));
        $category = $validated['category'] ?? null;

        $restaurants = $this->visibleRestaurants()
            ->when($search !== '', fn (Builder $q) => $q->where(
                fn (Builder $q2) => $q2->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
            ))
            ->when($category !== null && $category !== '', fn (Builder $q) => $q->where('category', $category))
            ->orderByDesc('avg_rating')
            ->paginate(20, self::RESTAURANT_COLUMNS);

        return RestaurantResource::collection($restaurants);
    }

    /** GET /customer/restaurant-categories */
    public function restaurantCategories(): JsonResponse
    {
        $categories = $this->visibleRestaurants()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json(['data' => $categories->values()->all()]);
    }

    /** GET /customer/menu-categories */
    public function menuCategories(): JsonResponse
    {
        // Distinct name + emoji only (same as ItemComponent), so no id here.
        $categories = Category::query()
            ->where('type', Category::TYPE_FOOD)
            ->where('is_active', true)
            ->select('name', 'emoji')
            ->distinct()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories->map(fn (Category $c) => [
                'name'  => $c->name,
                'emoji' => $c->emoji,
            ])->all(),
        ]);
    }

    /** GET /customer/menu-items?category=<name>&search=&page= */
    public function menuItems(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'search'   => ['nullable', 'string', 'max:100'],
        ]);

        $category = $validated['category'] ?? null;
        $search   = trim((string) ($validated['search'] ?? ''));
        // A typed % or _ must be searched literally, not act as a wildcard.
        $like     = '%' . addcslashes($search, '%_\\') . '%';

        $items = $this->visibleFoods()
            ->when($search !== '', fn (Builder $q) => $q->where(
                fn (Builder $q2) => $q2->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('restaurant', fn (Builder $r) => $r->where('name', 'like', $like))
            ))
            ->when($category !== null && $category !== '', fn (Builder $q) => $q->whereHas(
                'category',
                fn (Builder $c) => $c->where('name', $category)
            ))
            ->orderBy('sort_order')
            ->paginate(20);

        return FoodResource::collection($items);
    }

    /**
     * GET /customer/restaurants/{id}
     * One restaurant + its available menu. Same grouping as the website page
     * (RestaurantDetailComponent): the restaurant's own active menu sections
     * first (by sort_order), then the foods without a section by category.
     */
    public function restaurant(int $id): JsonResponse
    {
        $restaurant = $this->visibleRestaurants()->find($id);
        abort_if($restaurant === null, 404, 'Restaurant not found.');

        $foods = Food::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->with('category:id,name,emoji', 'restaurant:id,name,slug', 'menuSection:id,name,sort_order,is_active')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $sectionGroups  = [];
        $categoryGroups = [];

        foreach ($foods as $food) {
            $section = $food->menuSection;

            if ($section && $section->is_active) {
                $sectionGroups[$section->id] ??= [
                    'id'    => $section->id,
                    'order' => $section->sort_order,
                    'title' => $section->name,
                    'emoji' => null,
                    'items' => [],
                ];
                $sectionGroups[$section->id]['items'][] = $food;

                continue;
            }

            $categoryKey = $food->category_id ?? 0;

            $categoryGroups[$categoryKey] ??= [
                'id'    => $categoryKey,
                'order' => 0,
                'title' => $food->category?->name ?? 'Other',
                'emoji' => $food->category?->emoji,
                'items' => [],
            ];
            $categoryGroups[$categoryKey]['items'][] = $food;
        }

        $groups = collect($sectionGroups)
            ->sortBy([['order', 'asc'], ['id', 'asc']])
            ->values()
            ->concat(array_values($categoryGroups))
            ->map(fn (array $group) => [
                'title' => $group['title'],
                'emoji' => $group['emoji'],
                'items' => FoodResource::collection(collect($group['items']))->resolve(),
            ])
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'restaurant'  => (new RestaurantResource($restaurant))->resolve(),
                'total_items' => $foods->count(),
                'groups'      => $groups,
            ],
        ]);
    }

    /**
     * GET /customer/menu-items/{id}
     * One food + its restaurant + up to 4 more items from the same
     * restaurant (FoodDetailComponent).
     */
    public function food(int $id): JsonResponse
    {
        $food = $this->visibleFoods()->find($id);
        abort_if($food === null, 404, 'This item is no longer available.');

        $restaurant = $this->visibleRestaurants()->find($food->restaurant_id);
        abort_if($restaurant === null, 404, 'This item is no longer available.');

        $moreItems = $this->visibleFoods()
            ->where('restaurant_id', $food->restaurant_id)
            ->where('id', '!=', $food->id)
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        return response()->json([
            'data' => [
                'food'       => (new FoodResource($food))->resolve(),
                'restaurant' => (new RestaurantResource($restaurant))->resolve(),
                'more_items' => FoodResource::collection($moreItems)->resolve(),
            ],
        ]);
    }

    /** Only restaurants the admin approved and has not blocked. */
    private function visibleRestaurants(): Builder
    {
        return Restaurant::query()
            ->where('is_active', true)
            ->where('is_approved', true);
    }

    /** Available food that belongs to a visible restaurant. */
    private function visibleFoods(): Builder
    {
        return Food::query()
            ->where('is_available', true)
            ->whereHas('restaurant', fn (Builder $q) => $q->where('is_active', true)->where('is_approved', true))
            ->with('category:id,name,emoji', 'restaurant:id,name,slug');
    }
}
