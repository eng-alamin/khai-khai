<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_FOOD    = 'food';
    public const TYPE_PRODUCT = 'product';
    public const TYPE_BAZAR   = 'bazar';
    public const TYPE_SERVICE = 'service';

    public const TYPES = [
        self::TYPE_FOOD    => 'Food',
        self::TYPE_PRODUCT => 'Product',
        self::TYPE_BAZAR   => 'Bazar',
        self::TYPE_SERVICE => 'Service',
    ];

    /** Category (1) -> Sub Category (2) -> Child Category (3) */
    public const MAX_LEVEL = 3;

    public const LEVEL_LABELS = [
        1 => 'Category',
        2 => 'Sub Category',
        3 => 'Child Category',
    ];

    protected $fillable = [
        'parent_id',
        'type',
        'name',
        'slug',
        'emoji',
        'image_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'parent_id'  => 'integer',
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Category $category): void {
            if (empty($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name);
            }
        });

        static::updating(function (Category $category): void {
            if ($category->isDirty('name') && ! $category->isDirty('slug')) {
                $category->slug = static::generateUniqueSlug($category->name, $category->id);
            }
        });
    }

    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        // Bangla naam theke slug khali ashle fallback
        $base    = Str::slug($name) ?: 'category';
        $slug    = $base;
        $counter = 1;

        while (
            static::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function foods(): HasMany
    {
        return $this->hasMany(Food::class, 'category_id');
    }

    /**
     * Returns the relevant HasMany relation based on this category's type.
     * food -> foods; product / bazar / service -> products (service-e ekhon item nai).
     */
    public function items(): HasMany
    {
        return $this->type === self::TYPE_FOOD ? $this->foods() : $this->products();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /** Top level Category (parent nai) */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Tree helpers
    |--------------------------------------------------------------------------
    */

    /** 1 = Category, 2 = Sub Category, 3 = Child Category */
    public function getLevelAttribute(): int
    {
        $level = 1;
        $node  = $this;

        while ($node->parent_id && $level < 10) {
            $node = $node->parent;
            if (! $node) {
                break;
            }
            $level++;
        }

        return $level;
    }

    public function getLevelLabelAttribute(): string
    {
        return self::LEVEL_LABELS[$this->level] ?? 'Category';
    }

    /** "মুদি বাজার › চাল › মিনিকেট" */
    public function getPathAttribute(): string
    {
        $names = [$this->name];
        $node  = $this;
        $guard = 0;

        while ($node->parent_id && $guard++ < 10) {
            $node = $node->parent;
            if (! $node) {
                break;
            }
            array_unshift($names, $node->name);
        }

        return implode(' › ', $names);
    }

    /** Ei category-r sob child / grand-child id */
    public function descendantIds(): array
    {
        $ids      = [];
        $frontier = [$this->id];

        while ($frontier) {
            $children = static::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            $children = array_values(array_diff($children, $ids));
            $ids      = array_merge($ids, $children);
            $frontier = $children;
        }

        return $ids;
    }

    /** Ei category-r niche koto level ache (child nai = 0) */
    public function maxSubtreeDepth(): int
    {
        $depth    = 0;
        $frontier = [$this->id];

        while ($depth < 10) {
            $children = static::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            if (! $children) {
                break;
            }
            $depth++;
            $frontier = $children;
        }

        return $depth;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
