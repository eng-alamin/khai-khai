<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\Order;

/**
 * One place for commission rules, so the vendor finance page, the admin
 * revenue page and the money records written at delivery always agree.
 *
 * Rate    : restaurants.commission_rate (a percentage, e.g. 15.00 = 15%).
 *           Falls back to the "default_commission_rate" admin setting.
 * Base    : orders.subtotal (food only; the delivery fee belongs to the rider).
 * Amounts : Taka with 2 decimals (the orders table stores Taka, not paisa).
 */
class CommissionService
{
    public function rateFor(Order $order): float
    {
        $order->loadMissing('restaurant');

        $rate = $order->restaurant?->commission_rate;

        return (float) ($rate ?? AdminSetting::get('default_commission_rate', 0));
    }

    /**
     * Column values for a platform_transactions row.
     *
     * @return array{commission_rate: float, platform_commission: float, vendor_amount: float, rider_amount: float}
     */
    public function split(Order $order): array
    {
        $rate       = $this->rateFor($order);
        $subtotal   = (float) $order->subtotal;
        $commission = round($subtotal * $rate / 100, 2);

        return [
            'commission_rate'     => $rate,
            'platform_commission' => $commission,
            'vendor_amount'       => round($subtotal - $commission, 2),
            'rider_amount'        => round((float) $order->delivery_fee, 2),
        ];
    }
}
