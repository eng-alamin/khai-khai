<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'emoji',
        'logo_url',
        'banner_url',
        'address',
        'city',
        'latitude',
        'longitude',
        'phone',
        'tag',
        'owner_id',
        'commission_rate',
        'is_open',
        'is_approved',
        'is_active',
        'avg_rating',
        'total_reviews',
    ];

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

    /** Approve howar jonno upload kora KYC document gulo (trade license, NID, ...) */
    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    /** Kon kon customer ei restaurant ta favorite/save rekheche */
    public function favoritedBy(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    /** Sob document approved kina — eta check kore full KYC complete kina bola jay */
    public function hasApprovedDocuments(): bool
    {
        return $this->documents()->approved()->exists();
    }
}