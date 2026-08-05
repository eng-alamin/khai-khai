<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\RiderEarning;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RiderDeliveryService
{
    /**
     * Mark a rider's picked-up order as delivered, create its earning
     * record, and log everything atomically.
     *
     * Shared by DashboardComponent and DeliveryOngoingComponent so both
     * "Complete Delivery" buttons behave identically (previously the two
     * components had slightly different, duplicated logic — one wrapped
     * in a transaction with activity logging, the other wasn't).
     */
    public function completeDelivery(int $orderId, User $rider): Order
    {
        return DB::transaction(function () use ($orderId, $rider) {
            $order = Order::where('id', $orderId)
                ->where('rider_id', $rider->id)
                ->where('status', 'picked_up')
                ->lockForUpdate()
                ->firstOrFail();

            $from = $order->status;

            $order->update([
                'status'       => 'delivered',
                'delivered_at' => now(),
            ]);

            OrderStatusLog::create([
                'order_id'    => $order->id,
                'from_status' => $from,
                'to_status'   => 'delivered',
                'changed_by'  => $rider->id,
            ]);

            RiderEarning::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'rider_id'      => $rider->id,
                    'amount'        => $order->delivery_fee,
                    'payout_status' => 'pending',
                ]
            );

            activity()
                ->causedBy($rider)
                ->performedOn($order)
                ->log('Rider completed delivery');

            return $order;
        });
    }
}
