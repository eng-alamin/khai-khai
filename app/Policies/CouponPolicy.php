<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function update(User $user, Coupon $coupon): bool
    {
        return $this->createdByUser($user, $coupon);
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $this->createdByUser($user, $coupon);
    }

    public function toggle(User $user, Coupon $coupon): bool
    {
        return $this->createdByUser($user, $coupon);
    }

    public function create(User $user): bool
    {
        return $user->isVendor() && $user->restaurant !== null;
    }

    private function createdByUser(User $user, Coupon $coupon): bool
    {
        return $user->isVendor() && $coupon->created_by === $user->id;
    }
}