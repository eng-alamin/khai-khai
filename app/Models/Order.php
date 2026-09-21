<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Order extends Model
{
    use SoftDeletes;

    // Explicit fillable — pricing, status, and payment_status are
    // server-calculated and must never be mass-assigned from request input.
    protected $fillable = [
        'order_number',
        'restaurant_id',
        'customer_id',
        'rider_id',
        'restaurant_snapshot',
        'customer_snapshot',
        'rider_snapshot',
        'coupon_snapshot',
        'delivery_address_id',
        'delivery_address_snapshot',
        'delivery_distance_km',
        'coupon_id',
        'payment_method',
        'special_instructions',
        'estimated_delivery_minutes',
        'order_source',
    ];

    // ─────────────────────────────────────────
    // CASTS
    // ─────────────────────────────────────────
    protected $casts = [
        // JSON column → automatically cast to array
        'delivery_address_snapshot' => 'array',

        // Enum columns
        'status'         => 'string',
        'payment_method' => 'string',
        'payment_status' => 'string',

        // Timestamps
        'estimated_delivery_at' => 'datetime',
        'delivered_at'          => 'datetime',
        'cancelled_at'          => 'datetime',
    ];

    /** Customer can cancel the order while it's in one of these statuses */
    public const CANCELLABLE_STATUSES = ['pending']; // customer

    /** The "Track" button is shown while the order is in one of these statuses */
    public const TRACKABLE_STATUSES = ['pending', 'confirmed', 'preparing', 'picked_up']; // customer

    // ─────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function riderProfile(): HasOneThrough
    {
        return $this->hasOneThrough(
            RiderProfile::class,
            User::class,
            'id',
            'user_id',
            'rider_id',
            'id'
        );
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    // NOTE: requires App\Models\PlatformTransaction, which does not yet
    // exist in this codebase — create it before calling this relation.
    public function transaction(): HasOne
    {
        return $this->hasOne(PlatformTransaction::class);
    }

    // NOTE: requires App\Models\CouponUsage, which does not yet exist in
    // this codebase — create it before calling this relation.
    public function couponUsage(): HasOne
    {
        return $this->hasOne(CouponUsage::class);
    }

    public function riderEarning(): HasOne
    {
        return $this->hasOne(RiderEarning::class);
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    public function getTotalQuantityAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function getItemsSummaryAttribute(): string
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        if ($items->isEmpty()) {
            return '';
        }

        $parts = $items->take(2)
            ->map(fn ($item) => "{$item->item_name} x{$item->quantity}")
            ->all();

        $remaining = $items->count() - 2;
        if ($remaining > 0) {
            $parts[] = "+{$remaining} more";
        }

        return implode(', ', $parts);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function getSubtotalInTakaAttribute(): string
    {
        return '৳' . number_format((float) $this->subtotal);
    }

    public function getTotalAmountInTakaAttribute(): string
    {
        return '৳' . number_format((float) $this->total_amount);
    }

    public function getDeliveryFeeInTakaAttribute(): string
    {
        return '৳' . number_format((float) $this->delivery_fee);
    }
}