<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSetting extends Model
{
    // Commission is NOT stored here. The single source of truth is
    // restaurants.commission_rate (see App\Services\CommissionService).
    // Open/closed status lives in restaurants.is_open.
    // scheduled_open_state is written only by the opening-hours sync.
    protected $fillable = [
        'restaurant_id',
        'auto_accept',
        'prep_time_min',
        'notification_sound',
        'min_order_amount',
        'auto_reject',
        'order_timeout_minutes',
        'use_opening_hours',
    ];

    protected $casts = [
        'use_opening_hours'    => 'boolean',
        'scheduled_open_state' => 'boolean',
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
