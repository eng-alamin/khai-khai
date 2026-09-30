<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    // used_count is incremented by redemption logic, not user input.
    protected $fillable = [
        'restaurant_id',
        'title',
        'description',
        'type',
        'discount_type',
        'discount_value',
        'applies_to',
        'target_id',
        'minimum_order_amount',
        'usage_limit',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
        'is_active'      => 'boolean',
    ];

    // ── Restaurant ────────────────────────────────────────
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    // ── Dynamic target (Category, Food or Product) ────────
    /**
     * Returns the related Category, Food or Product depending on
     * the value of `applies_to`.
     *
     * Usage:  $promotion->target()
     */
    public function target(): Category|Food|Product|null
    {
        return match ($this->applies_to) {
            'category'          => $this->category,
            'specific_food'     => $this->food,
            'specific_product'  => $this->product,
            default             => null,
        };
    }

    public function category()
    {
        // promotions table only has a generic `target_id` column
        // (no `category_id`), so all target relations key off it.
        return $this->belongsTo(Category::class, 'target_id');
    }

    public function food()
    {
        return $this->belongsTo(Food::class, 'target_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'target_id');
    }

    // ── Scopes ────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(fn ($q) =>
                         $q->whereNull('ends_at')
                           ->orWhere('ends_at', '>=', now())
                     );
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('ends_at')
                     ->where('ends_at', '<', now());
    }

    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    // ── Accessors ─────────────────────────────────────────
    public function getIsExpiredAttribute(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->starts_at && $this->starts_at->isFuture();
    }

    public function getIsRunningAttribute(): bool
    {
        return $this->is_active && ! $this->is_expired && ! $this->is_upcoming;
    }
}
