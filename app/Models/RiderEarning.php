<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderEarning extends Model
{
    // Financial record — payout lifecycle fields are set only by payout
    // processing code, never mass-assigned.
    protected $fillable = [
        'rider_id',
        'order_id',
        'amount',
        'base_fare',
        'distance_bonus',
        'tip_amount',
        'distance_km',
    ];
}
