<?php

namespace App\Livewire\Vendor;

use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewComponent extends Component
{
    use WithPagination;

    public string $filterRating  = 'all';   // all | 5 | 4 | 3 | 2 | 1
    public string $filterType    = 'all';   // all | food | delivery
    public string $search        = '';
    public string $sortBy        = 'latest'; // latest | highest | lowest

    protected $queryString = [
        'filterRating' => ['except' => 'all'],
        'filterType'   => ['except' => 'all'],
        'search'       => ['except' => ''],
        'sortBy'       => ['except' => 'latest'],
    ];

    /* ---------------------------------------------------------------
     | Reset pagination whenever a filter changes
     * ------------------------------------------------------------- */
    public function updatingFilterRating(): void  { $this->resetPage(); }
    public function updatingFilterType(): void    { $this->resetPage(); }
    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingSortBy(): void        { $this->resetPage(); }

    /* ---------------------------------------------------------------
     | Summary stats for the stat cards
     * ------------------------------------------------------------- */
    private function stats(): array
    {
        $restaurantId = Auth::user()->restaurant->id;

        $base = Review::query()
            ->where('restaurant_id', $restaurantId);

        $total        = (clone $base)->count();
        $avgFood      = (clone $base)->avg('food_rating')     ?? 0;
        $avgDelivery  = (clone $base)->whereNotNull('delivery_rating')->avg('delivery_rating') ?? 0;
        $fiveStar     = (clone $base)->where('food_rating', 5)->count();
        $ratingDist   = [];

        for ($i = 5; $i >= 1; $i--) {
            $count = (clone $base)->where('food_rating', $i)->count();
            $ratingDist[$i] = [
                'count'   => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100) : 0,
            ];
        }

        return compact('total', 'avgFood', 'avgDelivery', 'fiveStar', 'ratingDist');
    }

    /* ---------------------------------------------------------------
     | Paginated, filtered review list
     * ------------------------------------------------------------- */
    private function reviews()
    {
        $restaurantId = Auth::user()->restaurant->id;

        $query = Review::query()
            ->with(['customer', 'order'])
            ->where('restaurant_id', $restaurantId);

        // Rating filter
        if ($this->filterRating !== 'all') {
            $column = $this->filterType === 'delivery' ? 'delivery_rating' : 'food_rating';
            $query->where($column, (int) $this->filterRating);
        }

        // Type filter (has delivery rating or not)
        if ($this->filterType === 'delivery') {
            $query->whereNotNull('delivery_rating');
        }

        // Keyword search on comment
        if ($this->search !== '') {
            $query->where('comment', 'like', '%' . $this->search . '%');
        }

        // Sort
        match ($this->sortBy) {
            'highest' => $query->orderByDesc('food_rating'),
            'lowest'  => $query->orderBy('food_rating'),
            default   => $query->latest(),
        };

        return $query->paginate(10);
    }

    public function render()
    {
        return view('livewire.vendor.review-component', [
            'stats'   => $this->stats(),
            'reviews' => $this->reviews(),
        ])->layout('layouts.vendor', [
            'title'           => 'Reviews | KhaiKhai',
            'breadcrumbTitle' => 'Reviews',
        ]);
    }
}