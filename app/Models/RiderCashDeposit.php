<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderCashDeposit extends Model
{
    public const METHODS = ['cash', 'bkash', 'bank_transfer'];

    // Financial record: created only by RiderCashService (forceFill),
    // never mass-assigned from request input.
    protected $fillable = [];

    protected $casts = [
        'amount'       => 'decimal:2',
        'deposited_at' => 'datetime',
    ];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
