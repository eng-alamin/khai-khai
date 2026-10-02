<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * All coupon rules live here so the cart preview and the real checkout
 * can never disagree.
 *
 * Rules:
 *  - A coupon created by a VENDOR works only for that vendor's restaurant.
 *    A coupon not created by a vendor works for every restaurant.
 *  - Applies to vendor food orders only (the product cart does not use coupons).
 *  - Discount never makes the total negative.
 *  - Redemption is serialized with a row lock, so usage_limit and
 *    per_user_limit cannot be beaten by two simultaneous checkouts.
 */
class CouponService
{
    public const GENERIC_ERROR = 'এই কুপনটি ব্যবহার করা যাচ্ছে না।';

    /**
     * Check a code and calculate the discount. Writes nothing.
     * Pass $lock = true only inside a DB transaction (real checkout).
     *
     * @return array{coupon: ?Coupon, discount: float, error: ?string}
     */
    public function evaluate(
        string $code,
        User $user,
        ?int $restaurantId,
        float $subtotal,
        float $deliveryFee,
        bool $lock = false
    ): array {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return $this->fail('কুপন কোড দিন।');
        }

        if ($restaurantId === null) {
            return $this->fail('কার্টে আইটেম যোগ করুন।');
        }

        $query = Coupon::query()->with('createdBy.restaurant:id,owner_id')->where('code', $code);

        if ($lock) {
            $query->lockForUpdate();
        }

        $coupon = $query->first();

        // Same message for "not found" and "not allowed" → codes can't be probed.
        if (! $coupon || ! $coupon->is_usable) {
            return $this->fail(self::GENERIC_ERROR);
        }

        $owner = $coupon->createdBy;
        if ($owner && $owner->isVendor() && $owner->restaurant?->id !== $restaurantId) {
            return $this->fail('এই কুপনটি এই রেস্টুরেন্টে প্রযোজ্য নয়।');
        }

        $min = (float) $coupon->min_order_amount;
        if ($min > 0 && $subtotal < $min) {
            return $this->fail('এই কুপনের জন্য সর্বনিম্ন অর্ডার ৳' . number_format($min) . '।');
        }

        if ($coupon->per_user_limit !== null) {
            $used = CouponUsage::query()
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();

            if ($used >= $coupon->per_user_limit) {
                return $this->fail('আপনি এই কুপনটি আগেই ব্যবহার করেছেন।');
            }
        }

        $discount = $this->calculateDiscount($coupon, $subtotal, $deliveryFee);

        if ($discount <= 0) {
            return $this->fail(self::GENERIC_ERROR);
        }

        return ['coupon' => $coupon, 'discount' => $discount, 'error' => null];
    }

    public function calculateDiscount(Coupon $coupon, float $subtotal, float $deliveryFee): float
    {
        $value = (float) $coupon->value;

        $discount = match ($coupon->type) {
            'percentage'    => $this->percentage($coupon, $subtotal, $value),
            'fixed_amount'  => min($value, $subtotal),
            'free_delivery' => $deliveryFee,
            default         => 0.0,
        };

        return round(max(0.0, min($discount, $subtotal + $deliveryFee)), 2);
    }

    private function percentage(Coupon $coupon, float $subtotal, float $percent): float
    {
        $discount = $subtotal * min($percent, 100.0) / 100;

        if ($coupon->max_discount !== null && (float) $coupon->max_discount > 0) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return $discount;
    }

    /**
     * Record the redemption. Call INSIDE the checkout transaction, after the
     * order row exists and evaluate(..., lock: true) has passed.
     */
    public function redeem(Coupon $coupon, Order $order, User $user, float $discount): void
    {
        CouponUsage::create([
            'coupon_id'        => $coupon->id,
            'user_id'          => $user->id,
            'order_id'         => $order->id,
            'discount_type'    => $coupon->type === 'percentage' ? 'percentage' : 'fixed',
            'discount_value'   => $coupon->value,
            'discount_applied' => $discount,
            'used_at'          => now(),
        ]);

        // used_count is not mass-assignable; atomic increment keeps it exact.
        Coupon::query()->whereKey($coupon->id)->increment('used_count');
    }

    /**
     * Give the coupon back when an order is cancelled/rejected, so a cancelled
     * order does not burn the customer's single use or the coupon's limit.
     * Safe to call for orders without a coupon, and safe to call twice.
     */
    public function release(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $usage = CouponUsage::query()->where('order_id', $order->id)->lockForUpdate()->first();

            if (! $usage) {
                return;
            }

            Coupon::query()
                ->whereKey($usage->coupon_id)
                ->where('used_count', '>', 0)
                ->decrement('used_count');

            $usage->delete();
        });
    }

    /** @return array{coupon: null, discount: float, error: string} */
    private function fail(string $message): array
    {
        return ['coupon' => null, 'discount' => 0.0, 'error' => $message];
    }
}
