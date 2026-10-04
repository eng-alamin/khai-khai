<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderEarning extends Model
{
    // Earned ledger only - one row per delivered order. Payout state is
    // tracked in rider_payouts, not here.
    protected $fillable = [
        'rider_id',
        'order_id',
        'amount',
        'base_fare',
        'distance_bonus',
        'tip_amount',
        'distance_km',
    ];

    // Money columns are decimal(15,2) taka in the DB.
    protected $casts = [
        'amount'         => 'decimal:2',
        'base_fare'      => 'decimal:2',
        'distance_bonus' => 'decimal:2',
        'tip_amount'     => 'decimal:2',
        'distance_km'    => 'decimal:2',
    ];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
