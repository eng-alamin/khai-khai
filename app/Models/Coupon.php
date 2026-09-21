<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    // used_count is incremented by redemption logic, not user input.
    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount',
        'usage_limit',
        'per_user_limit',
        'valid_from',
        'valid_until',
        'is_active',
        'created_by',
    ];

    // FIX: value/min_order_amount/max_discount are decimal(10,2) and
    // decimal(12,2) taka in the DB (see coupons migration), not integer
    // paisa. The previous 'integer' casts truncated the cents on every
    // read/write.
    protected $casts = [
        'valid_from'        => 'datetime',
        'valid_until'       => 'datetime',
        'is_active'         => 'boolean',
        'usage_limit'       => 'integer',
        'used_count'        => 'integer',
        'per_user_limit'    => 'integer',
        'value'             => 'decimal:2',
        'min_order_amount'  => 'decimal:2',
        'max_discount'      => 'decimal:2',
    ];

    // ── Relations ─────────────────────────────────────────

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Computed Attributes ───────────────────────────────

    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->valid_from && $this->valid_from->isFuture();
    }

    public function getIsExhaustedAttribute(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    public function getIsUsableAttribute(): bool
    {
        return $this->is_active
            && ! $this->is_expired
            && ! $this->is_upcoming
            && ! $this->is_exhausted;
    }

    // FIX: no /100 division — min_order_amount is already stored in taka.
    public function getMinOrderTakaAttribute(): ?string
    {
        return $this->min_order_amount !== null
            ? number_format((float) $this->min_order_amount, 0)
            : null;
    }

    // FIX: no /100 division — max_discount is already stored in taka.
    public function getMaxDiscountTakaAttribute(): ?string
    {
        return $this->max_discount !== null
            ? number_format((float) $this->max_discount, 0)
            : null;
    }

    // ── Scope ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()))
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'));
    }
}