<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $guarded = [];

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

    // ── Dynamic target (Category or MenuItem) ─────────────
    /**
     * Returns the related MenuCategory or MenuItem
     * depending on the value of `applies_to`.
     *
     * Usage:  $promotion->target   (auto-resolved via __get)
     *         $promotion->loadTarget()
     */
    public function target(): MenuCategory|MenuItem|null
    {
        return match ($this->applies_to) {
            'category'      => $this->category,
            'specific_item' => $this->menuItem,
            default         => null,
        };
    }

    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'item_id');
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
