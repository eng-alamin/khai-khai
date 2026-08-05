<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Food extends Model
{
    use SoftDeletes;

    protected $table = 'foods';

    protected $guarded = [];

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

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}