<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    /**
     * Fixed homepage sections allowed for this product.
     * Kept centralized here so Livewire components / validation
     * rules can reuse the same source of truth.
     */
    public const SECTION_SPECIAL_OFFER = 'special_offer';
    public const SECTION_TRENDING      = 'trending';
    public const SECTION_BEST_SELLER   = 'best_seller';

    public const SECTIONS = [
        self::SECTION_SPECIAL_OFFER,
        self::SECTION_TRENDING,
        self::SECTION_BEST_SELLER,
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'compare_price',
        'emoji',
        'image_url',
        'section',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price'         => 'decimal:2',
        'compare_price' => 'decimal:2',
        'sort_order'    => 'integer',
        'is_active'     => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (empty($product->slug)) {
                $product->slug = static::generateUniqueSlug($product->name);
            }
        });

        static::updating(function (Product $product): void {
            // Regenerate slug only if the name changed and slug wasn't explicitly set by the caller.
            if ($product->isDirty('name') && ! $product->isDirty('slug')) {
                $product->slug = static::generateUniqueSlug($product->name, $product->id);
            }
        });
    }

    /**
     * Generate a unique slug from the given name, excluding the given model id (for updates).
     */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
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

    public function scopeForSection(Builder $query, string $section): Builder
    {
        return $query->where('section', $section);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Whether the product has a discount (compare_price is higher than price).
     */
    public function getHasDiscountAttribute(): bool
    {
        return $this->compare_price !== null && (float) $this->compare_price > (float) $this->price;
    }

    /**
     * Discount percentage, rounded to nearest integer, or null if no discount.
     */
    public function getDiscountPercentAttribute(): ?int
    {
        if (! $this->has_discount) {
            return null;
        }

        return (int) round((((float) $this->compare_price - (float) $this->price) / (float) $this->compare_price) * 100);
    }

    /**
     * Route key for slug-based routing: Route::model('product', Product::class)
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}