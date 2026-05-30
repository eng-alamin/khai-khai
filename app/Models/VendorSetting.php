<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorSetting extends Model
{
    protected $guarded = [];
    
    /**
     * VendorSetting → Restaurant (Many to 1)
     * এই setting কোন restaurant-এর।
     * vendor_settings.restaurant_id → restaurants.id
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
