<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $guarded = [];
    
    /**
     * Restaurant → User (Many to 1)
     * এই restaurant-এর মালিক কোন user।
     * restaurants.owner_id → users.id
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Restaurant → VendorSetting (1 to 1)
     * প্রতিটি restaurant-এর একটাই setting row থাকে।
     * vendor_settings.restaurant_id = restaurants.id
     */
    public function settings(): HasOne
    {
        return $this->hasOne(VendorSetting::class);
    }
}
