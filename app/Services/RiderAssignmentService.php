<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrderRiderAssignment;
use App\Models\User;

/**
 * Keeps the history of which rider held which order.
 *
 * Call both methods INSIDE the same database transaction that changes
 * orders.rider_id, so the order and its history can never disagree.
 */
class RiderAssignmentService
{
    /** A rider has just taken the order. */
    public function open(int $orderId, int $riderId): OrderRiderAssignment
    {
        return OrderRiderAssignment::create([
            'order_id'    => $orderId,
            'rider_id'    => $riderId,
            'assigned_at' => now(),
            'outcome'     => OrderRiderAssignment::OUTCOME_ACTIVE,
        ]);
    }

    /**
     * The order's current assignment is over.
     *
     * Safe to call when there is no active row (old orders): it then does
     * nothing and returns false.
     *
     * @param  string     $outcome  One of OrderRiderAssignment::OUTCOMES (not 'active').
     * @param  User|null  $endedBy  Who ended it (null = the rider acted alone / system).
     */
    public function close(int $orderId, string $outcome, ?User $endedBy = null, ?string $reason = null): bool
    {
        if ($outcome === OrderRiderAssignment::OUTCOME_ACTIVE
            || ! in_array($outcome, OrderRiderAssignment::OUTCOMES, true)) {
            throw new \InvalidArgumentException('Unknown assignment outcome.');
        }

        $affected = OrderRiderAssignment::query()
            ->where('order_id', $orderId)
            ->where('outcome', OrderRiderAssignment::OUTCOME_ACTIVE)
            ->update([
                'outcome'    => $outcome,
                'ended_at'   => now(),
                'ended_by'   => $endedBy?->id,
                'reason'     => $reason !== null ? mb_substr($reason, 0, 255) : null,
                'updated_at' => now(),
            ]);

        return $affected > 0;
    }
}
