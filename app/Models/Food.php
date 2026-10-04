<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Food extends Model
{
    use SoftDeletes;

    protected $table = 'foods';

    // is_featured is admin/promo-controlled and excluded from mass assignment.
    protected $fillable = [
        'restaurant_id',
        'category_id',
        'menu_section_id',
        'name',
        'description',
        'price',
        'compare_price',
        'emoji',
        'image_url',
        'is_available',
        'sort_order',
    ];

    protected $casts = [
        'price'         => 'decimal:2',
        'compare_price' => 'decimal:2',
        'is_featured'   => 'boolean',
        'is_available'  => 'boolean',
        'sort_order'    => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** Restaurant er nijer menu section (optional) — e.g. "Combo Deals" */
    public function menuSection()
    {
        return $this->belongsTo(MenuSection::class, 'menu_section_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /** Ei food item customer order korle je order_items row gulo toiri hoy */
    public function orderItems(): MorphMany
    {
        return $this->morphMany(OrderItem::class, 'orderable');
    }

    /** Kon kon customer ei food item ta favorite/save rekheche */
    public function favoritedBy(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }
}