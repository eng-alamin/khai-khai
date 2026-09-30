<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ItemOptionGroup extends Model
{
    public const TYPE_SINGLE   = 'single';
    public const TYPE_MULTIPLE = 'multiple';

    protected $fillable = [
        'optionable_type',
        'optionable_id',
        'name',
        'selection_type',
        'is_required',
        'max_select',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
        'max_select'  => 'integer',
        'sort_order'  => 'integer',
    ];

    /** Food ba Product, jar e group ta */
    public function optionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function values(): HasMany
    {
        return $this->hasMany(ItemOptionValue::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isSingleSelect(): bool
    {
        return $this->selection_type === self::TYPE_SINGLE;
    }

    public function isMultiSelect(): bool
    {
        return $this->selection_type === self::TYPE_MULTIPLE;
    }
}
