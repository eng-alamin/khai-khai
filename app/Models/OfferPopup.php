<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OfferPopup extends Model
{
    protected $fillable = [
        'badge',
        'highlight',
        'highlight_sub',
        'title',
        'message',
        'chips',
        'button_text',
        'button_url',
        'repeat_hours',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'repeat_hours' => 'integer',
        'starts_at'    => 'datetime',
        'ends_at'      => 'datetime',
    ];

    /** Active + schedule window-er moddhe thaka popup gulo. */
    public function scopeLive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /** Customer ke je ekta popup dekhabo (sobcheye notun live ta). */
    public static function current(): ?self
    {
        return static::live()->orderByDesc('id')->first();
    }

    /** "রেস্টুরেন্ট, বাজার" => ['রেস্টুরেন্ট', 'বাজার'] */
    public function getChipsListAttribute(): array
    {
        return collect(explode(',', (string) $this->chips))
            ->map(fn ($c) => trim($c))
            ->filter()
            ->take(6)
            ->values()
            ->all();
    }

    /** Live status label for admin list. */
    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_active) {
            return 'Inactive';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'Scheduled';
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'Expired';
        }

        return 'Live';
    }
}
