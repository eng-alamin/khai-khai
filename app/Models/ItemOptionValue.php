<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemOptionValue extends Model
{
    protected $fillable = [
        'item_option_group_id',
        'name',
        'extra_price',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'extra_price' => 'decimal:2',
        'sort_order'  => 'integer',
        'is_active'   => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ItemOptionGroup::class, 'item_option_group_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getExtraPriceInTakaAttribute(): string
    {
        return '৳' . number_format((float) $this->extra_price);
    }
}
