<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\PlatformTransaction;
use App\Models\RiderEarning;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RiderDeliveryService
{
    public function __construct(
        private readonly CommissionService $commission,
        private readonly OrderNotifier $notifier
    ) {
    }

    /**
     * Mark a rider's picked-up order as delivered and, in the same
     * transaction:
     *  - mark a cash-on-delivery order as paid (the rider collected the cash),
     *  - create the rider's earning record,
     *  - create the platform transaction (commission / vendor / rider split)
     *    for vendor (food) orders,
     *  - write the status log and the activity log.
     *
     * Shared by DashboardComponent and DeliveryOngoingComponent so both
     * "Complete Delivery" buttons behave identically.
     */
    public function completeDelivery(int $orderId, User $rider): Order
    {
        $delivered = DB::transaction(function () use ($orderId, $rider) {
            $order = Order::where('id', $orderId)
                ->where('rider_id', $rider->id)
                ->where('status', 'picked_up')
                ->lockForUpdate()
                ->firstOrFail();

            $from = $order->status;

            // status, delivered_at and payment_status are intentionally not
            // mass-assignable on Order, so forceFill() is used here.
            $order->forceFill([
                'status'         => 'delivered',
                'delivered_at'   => now(),
                // A reported problem is over once the order is really delivered.
                'delivery_issue_reason' => null,
                'delivery_issue_at'     => null,
                'payment_status' => $order->payment_method === 'cash_on_delivery'
                    ? 'paid'
                    : $order->payment_status,
            ])->save();

            OrderStatusLog::create([
                'order_id'    => $order->id,
                'from_status' => $from,
                'to_status'   => 'delivered',
                'changed_by'  => $rider->id,
                'changed_type' => 'rider',
            ]);

            RiderEarning::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'rider_id' => $rider->id,
                    'amount'   => $order->delivery_fee,
                ]
            );

            // Admin (product) orders have no restaurant, so there is no
            // vendor commission to record for them.
            if ($order->isVendorOrder() && $order->restaurant_id !== null) {
                PlatformTransaction::firstOrCreate(
                    ['order_id' => $order->id],
                    $this->commission->split($order) + [
                        'gateway'           => $this->gatewayFor($order->payment_method),
                        'status'            => 'success',
                        'settlement_status' => 'pending',
                    ]
                );
            }

            activity()
                ->causedBy($rider)
                ->performedOn($order)
                ->log('Rider completed delivery');

            return $order;
        });

        // After the commit, so the customer is never told about a rolled-back delivery.
        $this->notifier->delivered($delivered);

        return $delivered;
    }

    /** orders.payment_method -> platform_transactions.gateway */
    private function gatewayFor(?string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'bkash', 'nagad', 'card' => $paymentMethod,
            default                  => 'cod',
        };
    }
}
