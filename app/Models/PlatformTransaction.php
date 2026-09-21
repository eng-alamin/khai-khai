<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformTransaction extends Model
{
    // Payment/settlement record created server-side when an order's
    // payment is processed — status, settlement_status, and the money
    // split columns are set only by payment-processing/settlement code,
    // never mass-assigned from request input.
    protected $fillable = [
        'order_id',
        'vendor_amount',
        'rider_amount',
        'platform_commission',
        'commission_rate',
        'gateway',
        'gateway_txn_id',
        'gateway_fee',
        'status',
        'settlement_status',
        'settled_at',
    ];

    protected $casts = [
        'vendor_amount'       => 'decimal:2',
        'rider_amount'        => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'commission_rate'     => 'decimal:2',
        'gateway_fee'         => 'decimal:2',
        'settled_at'          => 'datetime',
    ];

    public const STATUSES = [
        'pending',
        'processing',
        'success',
        'failed',
        'refunded',
        'partial_refund',
    ];

    public const SETTLEMENT_STATUSES = [
        'pending',
        'paid',
        'failed',
    ];

    public const GATEWAYS = [
        'bkash',
        'nagad',
        'card',
        'cod',
    ];

    // ── Relations ─────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ── Computed Attributes ───────────────────────────────

    public function getIsSuccessfulAttribute(): bool
    {
        return $this->status === 'success';
    }

    public function getIsSettledAttribute(): bool
    {
        return $this->settlement_status === 'paid';
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeUnsettled($query)
    {
        return $query->where('settlement_status', 'pending');
    }
}