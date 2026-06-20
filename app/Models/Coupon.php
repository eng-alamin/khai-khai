<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    protected $guarded = [];

    protected $casts = [
        'valid_from'    => 'datetime',
        'valid_until'   => 'datetime',
        'is_active'     => 'boolean',
        'usage_limit'   => 'integer',
        'used_count'    => 'integer',
        'per_user_limit'=> 'integer',
        'min_order_amount' => 'integer',   // paisa
        'max_discount'     => 'integer',   // paisa
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

    /** Coupon valid_until পার হয়ে গেছে কিনা */
    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /** Coupon এখনো শুরু হয়নি */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->valid_from && $this->valid_from->isFuture();
    }

    /** Usage limit শেষ হয়ে গেছে কিনা */
    public function getIsExhaustedAttribute(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    /** বর্তমানে ব্যবহারযোগ্য কিনা */
    public function getIsUsableAttribute(): bool
    {
        return $this->is_active
            && ! $this->is_expired
            && ! $this->is_upcoming
            && ! $this->is_exhausted;
    }

    /** min_order_amount টাকায় (paisa → taka) */
    public function getMinOrderTakaAttribute(): ?string
    {
        return $this->min_order_amount
            ? number_format($this->min_order_amount / 100, 0)
            : null;
    }

    /** max_discount টাকায় */
    public function getMaxDiscountTakaAttribute(): ?string
    {
        return $this->max_discount
            ? number_format($this->max_discount / 100, 0)
            : null;
    }

    // ── Scope ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()))
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()));
    }
}