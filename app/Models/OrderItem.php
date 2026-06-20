<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $guarded = [];

    // ─────────────────────────────────────────
    // CASTS
    // ─────────────────────────────────────────
    protected $casts = [
        'item_price' => 'integer',  // paisa
        'line_total' => 'integer',  // paisa
        'quantity'   => 'integer',
    ];
 
    // ─────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────
 
    // কোন অর্ডারের আইটেম
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
 
    // মূল menu item (nullable — delete হলে null)
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
 
    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────
 
    // unit price টাকায়
    public function getPriceInTakaAttribute(): string
    {
        return '৳' . number_format($this->item_price / 100);
    }
 
    // line total টাকায়
    public function getLineTotalInTakaAttribute(): string
    {
        return '৳' . number_format($this->line_total / 100);
    }
}
