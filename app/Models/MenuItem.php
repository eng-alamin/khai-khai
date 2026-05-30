<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $guarded = [];

    /**
     * MenuItem → MenuCategory (Many to 1)
     * এই menu item কোন category তে আছে।
     * menu_items.menu_category_id → menu_categories.id
     */
    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    /**
     * MenuItem → Restaurant (Many to 1)
     * এই menu item কোন restaurant এর।
     * menu_items.restaurant_id → restaurants.id
     */
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
