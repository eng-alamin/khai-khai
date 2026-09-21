<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    // Financial settlement record — only period fields are ever set by an
    // admin action; every money/status column is computed or updated
    // through dedicated payout-processing code, never mass-assigned.
    protected $fillable = [
        'restaurant_id',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'period_start'      => 'date',
        'period_end'        => 'date',
        'orders_count'       => 'integer',
        'gross_subtotal'     => 'integer',
        'commission_rate'    => 'decimal:2',
        'commission_amount'  => 'integer',
        'adjustment_amount'  => 'integer',
        'net_amount'         => 'integer',
        'scheduled_at'       => 'datetime',
        'paid_at'            => 'datetime',
    ];
 
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
