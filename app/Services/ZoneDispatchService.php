<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\Order;
use App\Models\RiderProfile;

/**
 * Zone-based dispatch: who may see and accept a "ready" order.
 *
 * Rules:
 *  - Setting zone_dispatch_open_after_minutes = 0 (default) -> feature OFF,
 *    every rider sees every order (the old behaviour).
 *  - Otherwise an order is first offered only to riders whose zone covers
 *    the order's city (see config delivery.zone_cities). After N minutes
 *    from ready_at it opens to every rider.
 *  - Open to everyone, always, when: the rider has no zone, the order's
 *    city is unknown, the zone is not in the config, or ready_at is NULL
 *    (old orders). Better too open than an order nobody can take.
 *
 * The order's city is the restaurant's city (the rider goes there first).
 * Admin orders (no restaurant) use the delivery address city.
 */
class ZoneDispatchService
{
    public const SETTING = 'zone_dispatch_open_after_minutes';

    public function openAfterMinutes(): int
    {
        return max(0, (int) AdminSetting::get(self::SETTING, 0));
    }

    public function isEnabled(): bool
    {
        return $this->openAfterMinutes() > 0;
    }

    /** City the order is matched on, or null when unknown. */
    public function orderCity(Order $order): ?string
    {
        $city = $order->restaurant_id !== null
            ? $order->restaurant?->city
            : ($order->delivery_address_snapshot['city'] ?? null);

        $city = is_string($city) ? trim($city) : '';

        return $city !== '' ? $city : null;
    }

    /** Zones that cover this city. */
    public function zonesForCity(string $city): array
    {
        $zones = [];

        foreach ((array) config('delivery.zone_cities', []) as $zone => $cities) {
            if (in_array($city, (array) $cities, true)) {
                $zones[] = $zone;
            }
        }

        return $zones;
    }

    /** Has the zone-only window of this order ended (or does it not apply)? */
    public function isOpenToAll(Order $order): bool
    {
        $minutes = $this->openAfterMinutes();

        if ($minutes === 0 || $order->ready_at === null) {
            return true;
        }

        return $order->ready_at->copy()->addMinutes($minutes)->isPast();
    }

    /** Is the order inside this zone's own area? */
    public function inZone(Order $order, ?string $zone): bool
    {
        $city = $this->orderCity($order);

        if ($zone === null || $zone === '' || $city === null) {
            return true;
        }

        $cities = config('delivery.zone_cities.' . $zone);

        // A zone missing from the config must not lock the rider out.
        if (! is_array($cities)) {
            return true;
        }

        return in_array($city, $cities, true);
    }

    /** May this rider see / accept this order right now? */
    public function canTake(Order $order, ?RiderProfile $profile): bool
    {
        return $this->isOpenToAll($order) || $this->inZone($order, $profile?->zone);
    }
}
