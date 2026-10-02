<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Single place for order changes that must be race-safe.
 *
 * Every change re-reads the order row with a lock INSIDE a transaction and
 * checks the status again, so one action can never overwrite a change that
 * happened a moment earlier (vendor confirm, rider pickup, ...).
 *
 * $actor = null means "the system" (scheduled timeouts).
 */
class OrderTransitionService
{
    /**
     * Reasons a rider can choose when a picked-up order cannot be handed over.
     * The key comes from the page, the text is chosen here, so the page can
     * never write its own text into the database.
     */
    public const DELIVERY_ISSUES = [
        'customer_unreachable' => 'Customer is not answering',
        'wrong_address'        => 'Address is wrong or cannot be found',
        'customer_refused'     => 'Customer refused the order',
        'other'                => 'Other problem',
    ];

    /**
     * The only forward steps a vendor (or an admin, for product orders) can
     * take. Everything after "ready" belongs to the rider (pickUp, then
     * RiderDeliveryService::completeDelivery).
     */
    public const FORWARD_FLOW = [
        'pending'   => 'confirmed',
        'confirmed' => 'preparing',
        'preparing' => 'ready',
    ];

    public function __construct(
        private readonly CouponService $coupons,
        private readonly OrderNotifier $notifier
    ) {
    }

    /**
     * Cancel an order.
     *
     * @param  int                $orderId      Order to cancel.
     * @param  array<string,mixed> $scope       Ownership filter, e.g. ['customer_id' => 5]
     *                                          or ['restaurant_id' => 3]. Must not be empty.
     * @param  list<string>       $allowedFrom  Statuses this actor may cancel from.
     * @param  User|null          $actor        Who is cancelling (null = system).
     * @param  string             $reason       Saved in orders.cancel_reason.
     * @param  string             $logNote      Saved in the status log.
     * @param  string             $activityText Text for the activity log.
     * @return bool               true = cancelled, false = status changed meanwhile
     *                            (nothing was written).
     */
    public function cancel(
        int $orderId,
        array $scope,
        array $allowedFrom,
        ?User $actor,
        string $reason,
        string $logNote,
        string $activityText
    ): bool {
        if ($scope === []) {
            throw new \InvalidArgumentException('An ownership scope is required to cancel an order.');
        }

        $cancelledOrder = null;

        $done = DB::transaction(function () use ($orderId, $scope, $allowedFrom, $actor, $reason, $logNote, $activityText, &$cancelledOrder) {
            $order = Order::query()
                ->where('id', $orderId)
                ->where($scope)
                ->lockForUpdate()
                ->firstOrFail();

            // Status is checked again under the lock. A rider who already took
            // the order also blocks the cancel.
            if (! in_array($order->status, $allowedFrom, true) || $order->rider_id !== null) {
                return false;
            }

            $from = $order->status;

            $order->forceFill([
                'status'        => 'cancelled',
                'cancelled_at'  => now(),
                'cancelled_by'  => $actor?->id,
                'cancel_reason' => $reason,
            ])->save();

            OrderStatusLog::create([
                'order_id'     => $order->id,
                'from_status'  => $from,
                'to_status'    => 'cancelled',
                'changed_by'   => $actor?->id,
                'changed_type' => $this->changedType($actor),
                'note'         => $logNote,
            ]);

            // Give the coupon back so a cancelled order does not use it up.
            $this->coupons->release($order);

            activity()
                ->causedBy($actor)
                ->performedOn($order)
                ->withProperties(['from' => $from, 'to' => 'cancelled', 'reason' => $reason])
                ->log($activityText);

            $cancelledOrder = $order;

            return true;
        });

        // After the commit, so nobody is told about a rolled-back cancel.
        if ($done && $cancelledOrder !== null) {
            $this->notifier->cancelled($cancelledOrder, $actor);
        }

        return $done;
    }

    /**
     * Take an order back from a rider who accepted it but has not collected
     * the food yet. The order stays "ready" and goes back to the pool.
     *
     * @return bool true = rider removed, false = order is no longer in that state.
     */
    public function unassignRider(int $orderId, ?User $actor, string $reason): bool
    {
        $freedOrder = null;
        $riderId    = null;

        $done = DB::transaction(function () use ($orderId, $actor, $reason, &$freedOrder, &$riderId) {
            $order = Order::query()
                ->where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'ready' || $order->rider_id === null) {
                return false;
            }

            $riderId = $order->rider_id; // by-reference: used after the commit

            $order->forceFill([
                'rider_id'    => null,
                'assigned_at' => null,
                'accepted_at' => null,
            ])->save();

            // Only the activity log: an extra status-log row ("ready" -> "ready")
            // would distort the vendor dashboard's preparation-time numbers.
            activity()
                ->causedBy($actor)
                ->performedOn($order)
                ->withProperties(['rider_id' => $riderId, 'reason' => $reason])
                ->log('Rider unassigned from order');

            $freedOrder = $order;

            return true;
        });

        if ($done && $freedOrder !== null && $riderId !== null) {
            $this->notifier->riderRemoved($freedOrder, (int) $riderId, $actor);
        }

        return $done;
    }

    /**
     * Rider reports that a picked-up order cannot be handed over.
     * The order stays "picked_up" (rider keeps it, can still finish the
     * delivery); an admin is told and decides: retry or mark failed.
     *
     * @return bool false = not this rider's picked-up order, or already reported.
     */
    public function reportDeliveryIssue(int $orderId, User $rider, string $reasonKey, ?string $note = null): bool
    {
        if (! array_key_exists($reasonKey, self::DELIVERY_ISSUES)) {
            throw new \InvalidArgumentException('Unknown delivery issue reason.');
        }

        $text = self::DELIVERY_ISSUES[$reasonKey];
        $note = trim((string) $note);

        if ($note !== '') {
            $text .= ' — ' . mb_substr($note, 0, 150);
        }

        $flagged = null;

        $done = DB::transaction(function () use ($orderId, $rider, $text, $reasonKey, &$flagged) {
            $order = Order::query()
                ->where('id', $orderId)
                ->where('rider_id', $rider->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'picked_up' || $order->delivery_issue_at !== null) {
                return false;
            }

            $order->forceFill([
                'delivery_issue_reason' => mb_substr($text, 0, 255),
                'delivery_issue_at'     => now(),
            ])->save();

            // Activity log only: the order status did not change.
            activity()
                ->causedBy($rider)
                ->performedOn($order)
                ->withProperties(['reason_key' => $reasonKey, 'reason' => $text])
                ->log('Rider reported a delivery issue');

            $flagged = $order;

            return true;
        });

        if ($done && $flagged !== null) {
            $this->notifier->deliveryIssueReported($flagged);
        }

        return $done;
    }

    /**
     * Admin says: "try again". Clears the flag, the rider keeps the order.
     *
     * @return bool false = order is not a flagged picked-up order any more.
     */
    public function clearDeliveryIssue(int $orderId, User $admin): bool
    {
        $freed = null;

        $done = DB::transaction(function () use ($orderId, $admin, &$freed) {
            $order = Order::query()->where('id', $orderId)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'picked_up' || $order->delivery_issue_at === null) {
                return false;
            }

            $order->forceFill([
                'delivery_issue_reason' => null,
                'delivery_issue_at'     => null,
            ])->save();

            activity()
                ->causedBy($admin)
                ->performedOn($order)
                ->log('Admin cleared delivery issue (retry)');

            $freed = $order;

            return true;
        });

        if ($done && $freed !== null) {
            $this->notifier->deliveryIssueCleared($freed);
        }

        return $done;
    }

    /**
     * Admin marks a picked-up order as a failed delivery. The order becomes
     * "cancelled" (cancel_reason starts with "Delivery failed:"), the flag
     * columns stay as a record of what the rider reported.
     *
     * Deliberately NOT done here (needs a business decision first):
     *  - the coupon is NOT given back,
     *  - no rider earning and no vendor/platform transaction is created,
     *  - payment_status stays "pending" (cash was never collected).
     *
     * Works whether or not the rider reported anything, so an order whose
     * rider simply disappeared can be closed too.
     *
     * @return bool false = order is no longer "picked_up".
     */
    public function failDelivery(int $orderId, User $admin, string $reason): bool
    {
        $failed = null;

        $done = DB::transaction(function () use ($orderId, $admin, $reason, &$failed) {
            $order = Order::query()->where('id', $orderId)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'picked_up') {
                return false;
            }

            $text = mb_substr('Delivery failed: ' . $reason, 0, 1000);

            $order->forceFill([
                'status'        => 'cancelled',
                'cancelled_at'  => now(),
                'cancelled_by'  => $admin->id,
                'cancel_reason' => $text,
            ])->save();

            OrderStatusLog::create([
                'order_id'     => $order->id,
                'from_status'  => 'picked_up',
                'to_status'    => 'cancelled',
                'changed_by'   => $admin->id,
                'changed_type' => $this->changedType($admin),
                'note'         => $text,
            ]);

            activity()
                ->causedBy($admin)
                ->performedOn($order)
                ->withProperties(['from' => 'picked_up', 'to' => 'cancelled', 'reason' => $text])
                ->log('Admin marked delivery as failed');

            $failed = $order;

            return true;
        });

        if ($done && $failed !== null) {
            $this->notifier->deliveryFailed($failed);
        }

        return $done;
    }

    /**
     * Move an order one step forward: pending -> confirmed -> preparing -> ready.
     *
     * The order is re-read with a lock and checked again, so a double click
     * or a second browser tab can never push it through two steps.
     * Orders a rider already holds are never moved here.
     *
     * @param  int                 $orderId       Order to move.
     * @param  array<string,mixed> $scope         Ownership filter, e.g. ['restaurant_id' => 3]. Must not be empty.
     * @param  string              $expectedFrom  Status the person saw on screen.
     * @param  User|null           $actor         Who is moving it.
     * @param  string              $activityText  Text for the activity log.
     * @return string|null         The new status, or null when nothing was written.
     */
    public function advance(
        int $orderId,
        array $scope,
        string $expectedFrom,
        ?User $actor,
        string $activityText
    ): ?string {
        if ($scope === []) {
            throw new \InvalidArgumentException('An ownership scope is required to advance an order.');
        }

        $to = self::FORWARD_FLOW[$expectedFrom] ?? null;

        if ($to === null) {
            return null;
        }

        $advancedOrder = null;

        $done = DB::transaction(function () use ($orderId, $scope, $expectedFrom, $to, $actor, $activityText, &$advancedOrder) {
            $order = Order::query()
                ->where('id', $orderId)
                ->where($scope)
                ->lockForUpdate()
                ->first();

            if ($order === null || $order->status !== $expectedFrom || $order->rider_id !== null) {
                return false;
            }

            $order->forceFill(['status' => $to])->save();

            OrderStatusLog::create([
                'order_id'     => $order->id,
                'from_status'  => $expectedFrom,
                'to_status'    => $to,
                'changed_by'   => $actor?->id,
                'changed_type' => $this->changedType($actor),
            ]);

            activity()
                ->causedBy($actor)
                ->performedOn($order)
                ->withProperties(['from' => $expectedFrom, 'to' => $to])
                ->log($activityText);

            $advancedOrder = $order;

            return true;
        });

        // After the commit, so nobody is told about a rolled-back change.
        // "ready" also offers the order to online riders (see OrderNotifier).
        if ($done && $advancedOrder !== null) {
            $this->notifier->statusChanged($advancedOrder, $expectedFrom, $to);
        }

        return $done ? $to : null;
    }

    /**
     * Rider collected the food: "ready" -> "picked_up".
     * Status change and status log are written together or not at all.
     *
     * @return bool false = not this rider's "ready" order any more.
     */
    public function pickUp(int $orderId, User $rider): bool
    {
        $pickedOrder = null;

        $done = DB::transaction(function () use ($orderId, $rider, &$pickedOrder) {
            $order = Order::query()
                ->where('id', $orderId)
                ->where('rider_id', $rider->id)
                ->lockForUpdate()
                ->first();

            if ($order === null || $order->status !== 'ready') {
                return false;
            }

            $order->forceFill([
                'status'       => 'picked_up',
                'picked_up_at' => now(),
            ])->save();

            OrderStatusLog::create([
                'order_id'     => $order->id,
                'from_status'  => 'ready',
                'to_status'    => 'picked_up',
                'changed_by'   => $rider->id,
                'changed_type' => $this->changedType($rider),
            ]);

            activity()
                ->causedBy($rider)
                ->performedOn($order)
                ->log('Rider picked up order');

            $pickedOrder = $order;

            return true;
        });

        if ($done && $pickedOrder !== null) {
            $this->notifier->pickedUp($pickedOrder);
        }

        return $done;
    }

    /** orders status-log "changed_type" enum value for this actor. */
    private function changedType(?User $actor): string
    {
        return match ($actor?->role) {
            'admin'    => 'admin',
            'vendor'   => 'restaurant',
            'rider'    => 'rider',
            'customer' => 'customer',
            default    => 'system',
        };
    }
}