<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Order extends Model
{
    protected $guarded = [];

    // ─────────────────────────────────────────
    // CASTS
    // ─────────────────────────────────────────
    protected $casts = [
        // JSON column → automatically array হবে
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
 
    /** এই status গুলোতে থাকলে কাস্টমার অর্ডার cancel করতে পারবে */
    public const CANCELLABLE_STATUSES = ['pending']; //customer

    /** এই status গুলোতে "Track" বাটন দেখাবে */
    public const TRACKABLE_STATUSES = ['pending', 'confirmed', 'preparing', 'picked_up']; //customer

    // ─────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────
 
    // যে customer অর্ডার দিয়েছে
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
 
    // কোন রেস্তোরাঁর অর্ডার
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
 
    // কোন rider ডেলিভারি দিচ্ছে
    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    /**
     * Rider এর profile (vehicle, zone, live location) — সরাসরি order থেকে,
     * rider_id → users.id → rider_profiles.user_id হয়ে join করে।
     * Eager load: Order::with('riderProfile')
     */
    public function riderProfile(): HasOneThrough
    {
        return $this->hasOneThrough(
            RiderProfile::class,
            User::class,
            'id',           // users.id
            'user_id',      // rider_profiles.user_id
            'rider_id',     // orders.rider_id
            'id'            // users.id
        );
    }
 
    // ডেলিভারি ঠিকানা
    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
    }
 
    // কোন coupon ব্যবহার হয়েছে
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
 
    // অর্ডারের আইটেমগুলো
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
 
    // অর্ডারের status history
    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class);
    }
 
    // রিভিউ
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
 
    // payment transaction
    public function transaction(): HasOne
    {
        return $this->hasOne(PlatformTransaction::class);
    }
 
    // coupon usage
    public function couponUsage(): HasOne
    {
        return $this->hasOne(CouponUsage::class);
    }
 
    // rider earning
    public function riderEarning(): HasOne
    {
        return $this->hasOne(RiderEarning::class);
    }
 
    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────
 
    // মোট আইটেম সংখ্যা
    public function getTotalQuantityAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Short, human-readable summary of the items in this order, e.g.
     * "Chicken Biriyani x2, Cold Coffee x1, +3 more".
     * Relies on the `items` relation — eager-load it (::with('items'))
     * before using this accessor to avoid N+1 queries.
     */
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
 
    // অর্ডার pending কিনা
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
 
    // অর্ডার cancelled কিনা
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
 
    // অর্ডার delivered কিনা
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }
 
    // টাকার amount — paisa থেকে টাকায়
    public function getSubtotalInTakaAttribute(): string
    {
        return '৳' . number_format($this->subtotal / 100);
    }
 
    public function getTotalAmountInTakaAttribute(): string
    {
        return '৳' . number_format($this->total_amount / 100);
    }

    // ডেলিভারি ফি — paisa থেকে টাকায়
    public function getDeliveryFeeInTakaAttribute(): string
    {
        return '৳' . number_format($this->delivery_fee / 100);
    }
}