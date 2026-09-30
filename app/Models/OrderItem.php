<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderItem extends Model
{
    // Snapshot fields written server-side when building an order — no
    // role/price-privilege columns here, but kept explicit for consistency.
    protected $fillable = [
        'order_id',
        'orderable_type',
        'orderable_id',
        'item_image',
        'item_name',
        'item_price',
        'quantity',
        'discount_amount',
        'line_total',
        'emoji',
        'options',
    ];

    // ─────────────────────────────────────────
    // CASTS
    // ─────────────────────────────────────────
    // FIX: item_price/line_total are decimal(15,2) taka in the DB
    // (see order_items migration), not integer paisa. The previous
    // 'integer' cast silently truncated the cents on every read/write.
    protected $casts = [
        'item_price'       => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'line_total'       => 'decimal:2',
        'quantity'         => 'integer',
    ];

    // ─────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Original catalog item this line was ordered from.
     * Vendor order hole App\Models\Food, admin order hole App\Models\Product।
     * Item delete/deactivate hoye gele o eta null hobe — kintu item_name,
     * item_price, item_image snapshot column gulo taka thake, order history bhange na.
     */
    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    // FIX: no /100 division — item_price is already stored in taka.
    public function getPriceInTakaAttribute(): string
    {
        return '৳' . number_format((float) $this->item_price);
    }

    // FIX: no /100 division — line_total is already stored in taka.
    public function getLineTotalInTakaAttribute(): string
    {
        return '৳' . number_format((float) $this->line_total);
    }
}