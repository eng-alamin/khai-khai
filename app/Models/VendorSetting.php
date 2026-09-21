<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSetting extends Model
{
    // commission_rate is platform-controlled and excluded from mass assignment.
    protected $fillable = [
        'restaurant_id',
        'auto_accept',
        'prep_time_min',
        'notification_sound',
        'min_order_amount',
        'is_accepting_orders',
        'auto_reject',
        'order_timeout_minutes',
    ];

    /**
     * VendorSetting → Restaurant (many to one)
     * The restaurant this setting belongs to.
     * vendor_settings.restaurant_id → restaurants.id
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}