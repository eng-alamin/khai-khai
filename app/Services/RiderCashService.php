<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\RiderCashDeposit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cash cycle of Cash on Delivery.
 *
 *   collected   = total_amount of every delivered COD order of the rider
 *   deposited   = deposits an admin recorded after receiving the money
 *   cash in hand = collected - deposited   (what the rider still has to hand over)
 */
class RiderCashService
{
    public function collectedTotal(int $riderId): float
    {
        return (float) $this->collectedQuery()->where('rider_id', $riderId)->sum('total_amount');
    }

    public function depositedTotal(int $riderId): float
    {
        return (float) RiderCashDeposit::query()->where('rider_id', $riderId)->sum('amount');
    }

    public function cashInHand(int $riderId): float
    {
        return round($this->collectedTotal($riderId) - $this->depositedTotal($riderId), 2);
    }

    /**
     * Totals of many riders in 2 queries (for the admin list).
     *
     * @param  list<int> $riderIds
     * @return array<int, array{collected: float, deposited: float, in_hand: float}>
     */
    public function summaries(array $riderIds): array
    {
        if ($riderIds === []) {
            return [];
        }

        $collected = $this->collectedQuery()
            ->whereIn('rider_id', $riderIds)
            ->selectRaw('rider_id, SUM(total_amount) as total')
            ->groupBy('rider_id')
            ->pluck('total', 'rider_id');

        $deposited = RiderCashDeposit::query()
            ->whereIn('rider_id', $riderIds)
            ->selectRaw('rider_id, SUM(amount) as total')
            ->groupBy('rider_id')
            ->pluck('total', 'rider_id');

        $result = [];

        foreach ($riderIds as $id) {
            $c = (float) ($collected[$id] ?? 0);
            $d = (float) ($deposited[$id] ?? 0);

            $result[$id] = [
                'collected' => round($c, 2),
                'deposited' => round($d, 2),
                'in_hand'   => round($c - $d, 2),
            ];
        }

        return $result;
    }

    /**
     * Whole-platform numbers for the admin header.
     *
     * @return array{in_hand: float, deposited_today: float, deposited_month: float}
     */
    public function platformTotals(): array
    {
        $collected = (float) $this->collectedQuery()->sum('total_amount');
        $deposited = (float) RiderCashDeposit::query()->sum('amount');

        return [
            'in_hand'         => max(0.0, round($collected - $deposited, 2)),
            'deposited_today' => (float) RiderCashDeposit::query()->whereDate('deposited_at', today())->sum('amount'),
            'deposited_month' => (float) RiderCashDeposit::query()
                ->whereMonth('deposited_at', now()->month)
                ->whereYear('deposited_at', now()->year)
                ->sum('amount'),
        ];
    }

    /**
     * Admin received money from a rider.
     * Returns null when the amount is more than the rider still owes
     * (checked under a lock, so two admins cannot over-record).
     */
    public function recordDeposit(
        User $rider,
        float $amount,
        string $method,
        ?string $reference,
        ?string $note,
        User $admin
    ): ?RiderCashDeposit {
        $amount  = round($amount, 2);
        $deposit = null;

        DB::transaction(function () use ($rider, $amount, $method, $reference, $note, $admin, &$deposit) {
            // One rider at a time.
            User::query()->whereKey($rider->id)->lockForUpdate()->first();

            if ($amount <= 0 || $amount > $this->cashInHand($rider->id) + 0.001) {
                return;
            }

            $deposit = new RiderCashDeposit();
            $deposit->forceFill([
                'rider_id'     => $rider->id,
                'amount'       => $amount,
                'method'       => $method,
                'reference'    => $reference,
                'note'         => $note,
                'recorded_by'  => $admin->id,
                'deposited_at' => now(),
            ])->save();

            activity()
                ->causedBy($admin)
                ->performedOn($deposit)
                ->withProperties(['rider_id' => $rider->id, 'amount' => $amount, 'method' => $method])
                ->log('Admin recorded rider cash deposit');
        });

        if ($deposit !== null) {
            $this->notifyRider($rider, $deposit);
        }

        return $deposit;
    }

    // ── internals ────────────────────────────────────────────

    /** Delivered orders paid in cash, handled by a rider. */
    private function collectedQuery()
    {
        return Order::query()
            ->whereNotNull('rider_id')
            ->where('status', 'delivered')
            ->where('payment_method', 'cash_on_delivery');
    }

    private function notifyRider(User $rider, RiderCashDeposit $deposit): void
    {
        try {
            $left = max(0.0, $this->cashInHand($rider->id));

            Notification::create([
                'user_id'    => $rider->id,
                'type'       => 'cash_deposit_recorded',
                'title'      => 'ক্যাশ জমা গ্রহণ করা হয়েছে',
                'body'       => '৳' . number_format((float) $deposit->amount) . ' জমা গ্রহণ করা হয়েছে। এখন হাতে থাকা ক্যাশ: ৳' . number_format($left),
                'data'       => ['deposit_id' => $deposit->id, 'amount' => $deposit->amount],
                'action_url' => route('rider.finance', [], false),
                'priority'   => 'normal',
            ]);
        } catch (Throwable $e) {
            // A notification problem must never undo or break the deposit.
            Log::warning('Cash deposit notification failed', [
                'deposit_id' => $deposit->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
