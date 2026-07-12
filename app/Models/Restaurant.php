<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    protected $guarded = [];

    // ✅ Status helper — schema অনুযায়ী
    public function getStatusAttribute(): string
    {
        if (!$this->is_approved) return 'pending';
        if (!$this->is_active)   return 'blocked';
        return 'active';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(VendorSetting::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}