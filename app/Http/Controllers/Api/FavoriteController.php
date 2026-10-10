<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FoodResource;
use App\Http\Resources\RestaurantResource;
use App\Models\Favorite;
use App\Models\Food;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Favourite restaurants and foods. Only what the customer can actually order
 * from today is returned (approved + active restaurants, available foods).
 */
class FavoriteController extends Controller
{
    private const TYPES = [
        'restaurant' => Restaurant::class,
        'food'       => Food::class,
    ];

    /** One account cannot store an endless list. */
    private const MAX_PER_USER = 200;

    /** GET /favorites */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $restaurants = $this->visible(Restaurant::class, $this->idsOf($userId, Restaurant::class))->get();
        $foods       = $this->visible(Food::class, $this->idsOf($userId, Food::class))
            ->with('category:id,name,emoji', 'restaurant:id,name,slug')
            ->get();

        return response()->json([
            'data' => [
                'restaurants' => RestaurantResource::collection($restaurants)->resolve(),
                'menu_items'  => FoodResource::collection($foods)->resolve(),
            ],
        ]);
    }

    /** POST /favorites {type: restaurant|food, id} — idempotent. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:restaurant,food'],
            'id'   => ['required', 'integer', 'min:1'],
        ]);

        $class = self::TYPES[$data['type']];

        if (! $this->visible($class, [(int) $data['id']])->exists()) {
            return response()->json(['message' => 'This item is not available.', 'code' => 'not_available'], 404);
        }

        $user = $request->user();

        if (Favorite::query()->where('user_id', $user->id)->count() >= self::MAX_PER_USER) {
            return response()->json([
                'message' => 'You can save up to ' . self::MAX_PER_USER . ' favourites. Remove one first.',
                'code'    => 'favorite_limit',
            ], 422);
        }

        try {
            Favorite::query()->firstOrCreate([
                'user_id'          => $user->id,
                'favoritable_type' => $class,
                'favoritable_id'   => (int) $data['id'],
            ]);
        } catch (QueryException) {
            // Two taps at once hit the unique index: it is already saved.
        }

        return response()->json(['data' => ['favorited' => true]], 201);
    }

    /** DELETE /favorites/{type}/{id} — idempotent. */
    public function destroy(Request $request, string $type, int $id): JsonResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('favoritable_type', self::TYPES[$type])
            ->where('favoritable_id', $id)
            ->delete();

        return response()->json(['data' => ['favorited' => false]]);
    }

    /** @return list<int> newest favourite first */
    private function idsOf(int $userId, string $class): array
    {
        return Favorite::query()
            ->where('user_id', $userId)
            ->where('favoritable_type', $class)
            ->orderByDesc('id')
            ->pluck('favoritable_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /** Same visibility rules as CustomerCatalogController, limited to $ids. */
    private function visible(string $class, array $ids): Builder
    {
        if ($class === Restaurant::class) {
            return Restaurant::query()
                ->whereIn('id', $ids)
                ->where('is_active', true)
                ->where('is_approved', true)
                ->when($ids !== [], fn (Builder $q) => $q->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')'));
        }

        return Food::query()
            ->whereIn('id', $ids)
            ->where('is_available', true)
            ->whereHas('restaurant', fn (Builder $q) => $q->where('is_active', true)->where('is_approved', true))
            ->when($ids !== [], fn (Builder $q) => $q->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')'));
    }
}
