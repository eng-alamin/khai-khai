<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payout extends Model
{
    public const METHODS = ['bkash', 'nagad', 'bank_transfer', 'cash'];

    /** Statuses in which admin can still mark the payout paid or failed. */
    public const OPEN_STATUSES = ['pending', 'processing'];

    // Financial settlement record — only period fields are ever set by an
    // admin action; every money/status column is computed or updated
    // through dedicated payout-processing code, never mass-assigned.
    protected $fillable = [
        'restaurant_id',
        'period_start',
        'period_end',
    ];

    // FIX: gross_subtotal, commission_amount, adjustment_amount and
    // net_amount are decimal(12,2) taka in the DB (see payouts migration).
    // The previous 'integer' cast silently dropped the paisa part on every
    // read, so a payout of 1250.75 was shown (and re-saved) as 1250.
    protected $casts = [
        'period_start'      => 'date',
        'period_end'        => 'date',
        'orders_count'      => 'integer',
        'gross_subtotal'    => 'decimal:2',
        'commission_rate'   => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'net_amount'        => 'decimal:2',
        'scheduled_at'      => 'datetime',
        'paid_at'           => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /** Je admin ei payout process korechhe */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /** Platform transactions (one per delivered order) settled by this payout. */
    public function transactions(): HasMany
    {
        return $this->hasMany(PlatformTransaction::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
