<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;

/**
 * Offers screen of the mobile app (port of Livewire\Customer\OfferComponent).
 * Same list as the website: only coupons that are active, inside their dates
 * and not used up. Applying a coupon still goes through /cart/quote, which
 * re-checks every rule, so this list is informational only.
 */
class CouponController extends Controller
{
    /** GET /customer/coupons — newest first. */
    public function index(): JsonResponse
    {
        $coupons = Coupon::active()
            ->with('createdBy.restaurant:id,owner_id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (Coupon $c) => [
                'code'               => $c->code,
                'description'        => $c->description,
                'type'               => $c->type,
                'value'              => (float) $c->value,
                // Real numbers in taka. (The *_taka model accessors return
                // number_format() strings like "1,000", which the app cannot parse.)
                'max_discount_taka'  => $c->max_discount !== null && (float) $c->max_discount > 0 ? (float) $c->max_discount : null,
                'min_order_taka'     => $c->min_order_amount !== null && (float) $c->min_order_amount > 0 ? (float) $c->min_order_amount : null,
                'valid_until'        => $c->valid_until?->toIso8601String(),
                // Vendor coupons only work at that vendor's restaurant.
                'restaurant'         => ($c->createdBy && $c->createdBy->isVendor())
                    ? $c->createdBy->restaurant?->name
                    : null,
            ])
            ->values();

        return response()->json(['data' => $coupons]);
    }
}
