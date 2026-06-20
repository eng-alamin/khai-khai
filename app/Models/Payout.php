<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    protected $guarded = [];

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
