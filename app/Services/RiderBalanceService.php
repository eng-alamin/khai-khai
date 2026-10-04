<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\RiderEarning;
use App\Models\RiderPayout;

/**
 * One place that answers "how much can this rider still withdraw?".
 *
 *   available = all delivery earnings
 *             - payout requests that are pending / approved / processing / paid
 *             - cash he is still holding from Cash on Delivery orders
 *
 * Why the last line: with COD the rider already keeps the customer's money.
 * Paying his earnings again would pay him twice. When he deposits the cash,
 * the amount he owes drops and his balance opens up.
 *
 * Rejected and failed payout requests do not count, so that money becomes
 * available again. The cash rule can be switched off with the admin setting
 * "rider_cash_netting" = 0.
 */
class RiderBalanceService
{
    public function __construct(private readonly RiderCashService $cash)
    {
    }

    public function earnedTotal(int $riderId): float
    {
        return (float) RiderEarning::query()
            ->where('rider_id', $riderId)
            ->sum('amount');
    }

    public function reservedTotal(int $riderId): float
    {
        return (float) RiderPayout::query()
            ->where('rider_id', $riderId)
            ->whereIn('status', RiderPayout::RESERVING_STATUSES)
            ->sum('amount');
    }

    public function cashInHand(int $riderId): float
    {
        return $this->cash->cashInHand($riderId);
    }

    public function availableFor(int $riderId): float
    {
        return $this->availableFromParts(
            $this->earnedTotal($riderId),
            $this->reservedTotal($riderId),
            $this->cash->cashInHand($riderId)
        );
    }

    /** The one formula. Used for a single rider and for the admin list. */
    public function availableFromParts(float $earned, float $reserved, float $cashInHand): float
    {
        $owed = $this->nettingEnabled() ? max(0.0, $cashInHand) : 0.0;

        return max(0.0, round($earned - $reserved - $owed, 2));
    }

    public function nettingEnabled(): bool
    {
        return filter_var(AdminSetting::get('rider_cash_netting', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Money picture of many riders in a few queries (admin list).
     *
     * @param  list<int> $riderIds
     * @return array<int, array{collected: float, deposited: float, in_hand: float, earned: float, reserved: float, available: float}>
     */
    public function summaries(array $riderIds): array
    {
        if ($riderIds === []) {
            return [];
        }

        $cash = $this->cash->summaries($riderIds);

        $earned = RiderEarning::query()
            ->whereIn('rider_id', $riderIds)
            ->selectRaw('rider_id, SUM(amount) as total')
            ->groupBy('rider_id')
            ->pluck('total', 'rider_id');

        $reserved = RiderPayout::query()
            ->whereIn('rider_id', $riderIds)
            ->whereIn('status', RiderPayout::RESERVING_STATUSES)
            ->selectRaw('rider_id, SUM(amount) as total')
            ->groupBy('rider_id')
            ->pluck('total', 'rider_id');

        $result = [];

        foreach ($riderIds as $id) {
            $e = (float) ($earned[$id] ?? 0);
            $r = (float) ($reserved[$id] ?? 0);

            $result[$id] = $cash[$id] + [
                'earned'    => round($e, 2),
                'reserved'  => round($r, 2),
                'available' => $this->availableFromParts($e, $r, $cash[$id]['in_hand']),
            ];
        }

        return $result;
    }

    /** A rider may have only one request in progress at a time. */
    public function hasOpenRequest(int $riderId): bool
    {
        return RiderPayout::query()
            ->where('rider_id', $riderId)
            ->whereIn('status', RiderPayout::OPEN_STATUSES)
            ->exists();
    }
}
