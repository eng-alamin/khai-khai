<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Restaurant;
use App\Models\User;

/**
 * Builds the JSON "snapshot" arrays stored on an order.
 *
 * A snapshot freezes who/what was involved at the moment of the event, so
 * the order history stays correct even when the restaurant later renames
 * itself, a customer changes a phone number, a rider is replaced or a
 * coupon is edited. The Order model casts these columns to array.
 */
class OrderSnapshotService
{
    /** @return array{id: int, name: string, phone: ?string} */
    public function customer(User $user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'phone' => $user->phone,
        ];
    }

    /** @return array<string, mixed> */
    public function restaurant(Restaurant $restaurant): array
    {
        return [
            'id'        => $restaurant->id,
            'name'      => $restaurant->name,
            'phone'     => $restaurant->phone,
            'address'   => $restaurant->address,
            'city'      => $restaurant->city,
            'latitude'  => $restaurant->latitude,
            'longitude' => $restaurant->longitude,
        ];
    }

    /** @return array<string, mixed> */
    public function coupon(Coupon $coupon, float $discountApplied): array
    {
        return [
            'id'               => $coupon->id,
            'code'             => $coupon->code,
            'type'             => $coupon->type,
            'value'            => $coupon->value,
            'max_discount'     => $coupon->max_discount,
            'discount_applied' => round($discountApplied, 2),
        ];
    }

    /** @return array<string, mixed> */
    public function rider(User $rider): array
    {
        $profile = $rider->riderProfile;

        return [
            'id'            => $rider->id,
            'name'          => $rider->name,
            'phone'         => $rider->phone,
            'vehicle_type'  => $profile?->vehicle_type,
            'vehicle_plate' => $profile?->vehicle_plate,
        ];
    }
}
