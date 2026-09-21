<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    // Redemption record written server-side when a coupon is applied to an
    // order — discount_applied is the actual amount deducted and should
    // only ever be set by that redemption logic.
    protected $fillable = [
        'coupon_id',
        'user_id',
        'order_id',
        'discount_type',
        'discount_value',
        'discount_applied',
        'used_at',
    ];

    protected $casts = [
        'discount_value'    => 'decimal:2',
        'discount_applied'  => 'decimal:2',
        'used_at'           => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}