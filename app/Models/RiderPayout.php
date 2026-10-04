<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderPayout extends Model
{
    /** Requests that already "use up" part of the rider's balance. */
    public const RESERVING_STATUSES = ['pending', 'approved', 'processing', 'paid'];

    /** Requests that are still waiting to be finished (rider may not open another one). */
    public const OPEN_STATUSES = ['pending', 'approved', 'processing'];

    public const METHODS = ['bkash', 'bank'];

    // status/processed_by/admin_note are set only by the admin approval flow.
    protected $fillable = [
        'rider_id',
        'amount',
        'method',
        'account_number',
        'payment_account_snapshot',
    ];

    // amount is decimal(15,2) taka in the DB.
    protected $casts = [
        'amount'                   => 'decimal:2',
        'payment_account_snapshot' => 'array',
        'resolved_at'              => 'datetime',
    ];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
