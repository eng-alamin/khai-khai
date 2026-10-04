<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRiderAssignment extends Model
{
    // History record written only by RiderAssignmentService,
    // never mass-assigned from request input.
    protected $fillable = [
        'order_id',
        'rider_id',
        'assigned_at',
        'ended_at',
        'outcome',
        'ended_by',
        'reason',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'ended_at'    => 'datetime',
    ];

    public const OUTCOME_ACTIVE            = 'active';
    public const OUTCOME_DELIVERED         = 'delivered';
    public const OUTCOME_RELEASED_BY_RIDER = 'released_by_rider';
    public const OUTCOME_REMOVED_BY_STAFF  = 'removed_by_staff';
    public const OUTCOME_DELIVERY_FAILED   = 'delivery_failed';
    public const OUTCOME_RELEASED_BY_SYSTEM = 'released_by_system'; // auto-release after pickup timeout

    public const OUTCOMES = [
        self::OUTCOME_ACTIVE,
        self::OUTCOME_DELIVERED,
        self::OUTCOME_RELEASED_BY_RIDER,
        self::OUTCOME_REMOVED_BY_STAFF,
        self::OUTCOME_DELIVERY_FAILED,
        self::OUTCOME_RELEASED_BY_SYSTEM,
    ];

    // ── Relations ─────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('outcome', self::OUTCOME_ACTIVE);
    }
}
