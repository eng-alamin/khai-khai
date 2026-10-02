<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AdminSetting;
use App\Models\Order;
use App\Services\OrderTransitionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Runs every minute (routes/console.php) and fixes orders that would
 * otherwise stay stuck forever:
 *
 *  1. Vendor orders still "pending" after N minutes  -> auto-cancelled.
 *  2. Orders a rider accepted but never picked up    -> rider is removed and
 *     the order goes back to the pool of available orders.
 *
 * Both limits are admin settings (defaults below if the setting is missing):
 *   order_auto_cancel_minutes       (default 15)
 *   rider_pickup_timeout_minutes    (default 20)
 */
class ProcessOrderTimeouts extends Command
{
    protected $signature = 'orders:process-timeouts';

    protected $description = 'Auto-cancel unanswered vendor orders and release orders riders never picked up';

    /** Max orders handled per run, so one run can never take too long. */
    private const BATCH_LIMIT = 200;

    public function handle(OrderTransitionService $transitions): int
    {
        $cancelled = $this->cancelUnansweredOrders($transitions);
        $released  = $this->releaseStaleRiderClaims($transitions);

        if ($cancelled > 0 || $released > 0) {
            $this->info("Auto-cancelled: {$cancelled}, rider released: {$released}");
        }

        return self::SUCCESS;
    }

    private function cancelUnansweredOrders(OrderTransitionService $transitions): int
    {
        $minutes = max(1, (int) AdminSetting::get('order_auto_cancel_minutes', 15));

        $ids = Order::query()
            ->vendorOrders()
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->orderBy('id')
            ->limit(self::BATCH_LIMIT)
            ->pluck('id');

        $count = 0;

        foreach ($ids as $id) {
            try {
                $done = $transitions->cancel(
                    orderId: (int) $id,
                    scope: ['order_type' => Order::TYPE_VENDOR],
                    allowedFrom: ['pending'],
                    actor: null,
                    reason: 'Auto-cancelled: the restaurant did not respond in time.',
                    logNote: "No restaurant response within {$minutes} minutes",
                    activityText: 'Order auto-cancelled (restaurant timeout)'
                );

                $count += $done ? 1 : 0;
            } catch (\Throwable $e) {
                // One bad order must not stop the others.
                Log::error('Auto-cancel failed', ['order_id' => $id, 'error' => $e->getMessage()]);
            }
        }

        return $count;
    }

    private function releaseStaleRiderClaims(OrderTransitionService $transitions): int
    {
        $minutes = max(1, (int) AdminSetting::get('rider_pickup_timeout_minutes', 20));

        $ids = Order::query()
            ->where('status', 'ready')
            ->whereNotNull('rider_id')
            ->where('accepted_at', '<', now()->subMinutes($minutes))
            ->orderBy('id')
            ->limit(self::BATCH_LIMIT)
            ->pluck('id');

        $count = 0;

        foreach ($ids as $id) {
            try {
                $done = $transitions->unassignRider(
                    orderId: (int) $id,
                    actor: null,
                    reason: "Rider did not pick up within {$minutes} minutes"
                );

                $count += $done ? 1 : 0;
            } catch (\Throwable $e) {
                Log::error('Rider auto-release failed', ['order_id' => $id, 'error' => $e->getMessage()]);
            }
        }

        return $count;
    }
}
