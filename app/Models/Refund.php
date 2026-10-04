<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    public const REASONS = [
        'food_quality'  => 'Food quality',
        'missing_item'  => 'Missing item',
        'wrong_item'    => 'Wrong item',
        'late_delivery' => 'Late delivery',
        'other'         => 'Other',
    ];

    public const BEARERS = ['vendor', 'platform'];

    /** How the money was sent to the customer. */
    public const METHODS = ['bkash', 'nagad', 'bank_transfer', 'cash'];

    // Money and status columns are written only by RefundService.
    protected $fillable = [];

    protected $casts = [
        'amount'           => 'decimal:2',
        'vendor_deduction' => 'decimal:2',
        'paid_at'          => 'datetime',
        'cancelled_at'     => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }
}
