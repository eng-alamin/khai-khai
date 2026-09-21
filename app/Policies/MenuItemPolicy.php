<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

class MenuItemPolicy
{
    /**
     * A vendor may view/update/delete a menu item only if it belongs to
     * their own restaurant. Admins are intentionally not given a bypass
     * here — admin-side menu moderation, if ever added, should go through
     * its own explicit Admin policy method rather than this one.
     */
    public function view(User $user, MenuItem $menuItem): bool
    {
        return $this->ownsRestaurant($user, $menuItem);
    }

    public function update(User $user, MenuItem $menuItem): bool
    {
        return $this->ownsRestaurant($user, $menuItem);
    }

    public function delete(User $user, MenuItem $menuItem): bool
    {
        return $this->ownsRestaurant($user, $menuItem);
    }

    /**
     * A vendor may create menu items only for their own restaurant.
     * Called with the target restaurant_id, since a MenuItem instance
     * does not exist yet at create time.
     */
    public function create(User $user, int $restaurantId): bool
    {
        return $user->isVendor()
            && $user->restaurant
            && $user->restaurant->id === $restaurantId;
    }

    private function ownsRestaurant(User $user, MenuItem $menuItem): bool
    {
        return $user->isVendor()
            && $user->restaurant
            && $user->restaurant->id === $menuItem->restaurant_id;
    }
}