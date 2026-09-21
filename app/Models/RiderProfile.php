<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderProfile extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_type',
        'vehicle_plate',
        'license_number',
        'nid_number',
        'zone',
        'is_online',
        'is_approved',
        'total_deliveries',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'is_approved' => 'boolean',
        'avg_rating' => 'decimal:2',
        'current_lat' => 'decimal:7',
        'current_lng' => 'decimal:7',
        'location_updated_at' => 'datetime',
    ];

    /**
     * The user account (rider) this profile belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}