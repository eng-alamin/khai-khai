<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\Payout;
use App\Models\PlatformTransaction;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Vendor settlement.
 *
 *  generate()   : groups the delivered-order transactions of a date range per
 *                 restaurant and creates one "pending" payout for each.
 *  markPaid()   : admin sent the money; the transactions become "paid".
 *  markFailed() : payout cancelled / transfer failed; its transactions are
 *                 released, so the next generate() picks them up again.
 *
 * A transaction belongs to at most one payout (platform_transactions.payout_id),
 * so an order can never be paid out twice, even if two admins click at once.
 *
 * Refunds the restaurant carries (refunds.bearer = vendor, already paid to
 * the customer) are taken off the payout as a negative adjustment. A refund
 * belongs to at most one payout (refunds.payout_id). They are applied oldest
 * first and only while the net stays at zero or above; the rest wait for the
 * next payout.
 */
class VendorPayoutService
{
    /**
     * Per-restaurant totals for the range, without creating anything.
     *
     * @return Collection<int, array{restaurant_id:int, orders:int, gross:float, commission:float, net:float, rate:float, transaction_ids:list<int>}>
     */
    public function preview(Carbon $start, Carbon $end, ?int $restaurantId = null): Collection
    {
        return $this->withRefunds($this->summarize($this->eligible($start, $end, $restaurantId, false)), false);
    }

    /**
     * @return array{created:int, total_net:float}
     */
    public function generate(Carbon $start, Carbon $end, User $admin, ?int $restaurantId = null): array
    {
        return DB::transaction(function () use ($start, $end, $admin, $restaurantId) {
            // Lock the rows so a second admin cannot include the same orders.
            $groups = $this->withRefunds($this->summarize($this->eligible($start, $end, $restaurantId, true)), true);

            $created  = 0;
            $totalNet = 0.0;

            foreach ($groups as $group) {
                // Money and status columns are not mass-assignable on Payout, hence forceFill().
                $payout = new Payout();
                $payout->forceFill([
                    'payout_number'     => $this->nextNumber(),
                    'restaurant_id'     => $group['restaurant_id'],
                    'period_start'      => $start->toDateString(),
                    'period_end'        => $end->toDateString(),
                    'orders_count'      => $group['orders'],
                    'gross_subtotal'    => $group['gross'],
                    'commission_rate'   => $group['rate'],
                    'commission_amount' => $group['commission'],
                    'adjustment_amount' => -$group['refund_deduction'],
                    'net_amount'        => $group['net'],
                    'currency'          => 'BDT',
                    'status'            => 'pending',
                ])->save();

                foreach (array_chunk($group['transaction_ids'], 500) as $ids) {
                    PlatformTransaction::query()
                        ->whereIn('id', $ids)
                        ->whereNull('payout_id')
                        ->update(['payout_id' => $payout->id]);
                }

                if ($group['refund_ids'] !== []) {
                    Refund::query()
                        ->whereIn('id', $group['refund_ids'])
                        ->whereNull('payout_id')
                        ->update(['payout_id' => $payout->id]);
                }

                activity()
                    ->causedBy($admin)
                    ->performedOn($payout)
                    ->withProperties([
                        'period'           => $payout->period_start->toDateString() . ' → ' . $payout->period_end->toDateString(),
                        'orders'           => $group['orders'],
                        'refund_deduction' => $group['refund_deduction'],
                        'net'              => $group['net'],
                    ])
                    ->log('Admin generated vendor payout');

                $created++;
                $totalNet += $group['net'];
            }

            return ['created' => $created, 'total_net' => round($totalNet, 2)];
        });
    }

    public function markPaid(int $payoutId, User $admin, string $method, ?string $reference): bool
    {
        $paid = null;

        $ok = DB::transaction(function () use ($payoutId, $admin, $method, $reference, &$paid) {
            $payout = $this->lockOpen($payoutId);

            if ($payout === null) {
                return false;
            }

            $payout->forceFill([
                'status'                => 'paid',
                'payment_method'        => $method,
                'transaction_reference' => $reference,
                'paid_at'               => now(),
                'processed_by'          => $admin->id,
            ])->save();

            PlatformTransaction::query()
                ->where('payout_id', $payout->id)
                ->update(['settlement_status' => 'paid', 'settled_at' => now()]);

            activity()
                ->causedBy($admin)
                ->performedOn($payout)
                ->withProperties(['method' => $method, 'reference' => $reference, 'net' => $payout->net_amount])
                ->log('Admin marked vendor payout as paid');

            $paid = $payout;

            return true;
        });

        // After the commit, so the vendor is never told about a rolled-back payment.
        if ($ok && $paid !== null) {
            $this->notifyVendor($paid);
        }

        return $ok;
    }

    public function markFailed(int $payoutId, User $admin, string $reason): bool
    {
        return DB::transaction(function () use ($payoutId, $admin, $reason) {
            $payout = $this->lockOpen($payoutId);

            if ($payout === null) {
                return false;
            }

            $payout->forceFill([
                'status'         => 'failed',
                'failure_reason' => $reason,
                'processed_by'   => $admin->id,
            ])->save();

            // Release the transactions so a new payout can include them again.
            PlatformTransaction::query()
                ->where('payout_id', $payout->id)
                ->update(['payout_id' => null]);

            // The refunds taken off this payout wait for the next one.
            Refund::query()
                ->where('payout_id', $payout->id)
                ->update(['payout_id' => null]);

            activity()
                ->causedBy($admin)
                ->performedOn($payout)
                ->withProperties(['reason' => $reason])
                ->log('Admin marked vendor payout as failed / cancelled');

            return true;
        });
    }

    // ── internals ────────────────────────────────────────────

    /** Payout row, locked, only if it is still pending or processing. */
    private function lockOpen(int $payoutId): ?Payout
    {
        $payout = Payout::query()->whereKey($payoutId)->lockForUpdate()->first();

        if ($payout === null || ! in_array($payout->status, Payout::OPEN_STATUSES, true)) {
            return null;
        }

        return $payout;
    }

    /**
     * Transactions of delivered vendor orders that no payout has taken yet.
     *
     * @return Collection<int, object>
     */
    private function eligible(Carbon $start, Carbon $end, ?int $restaurantId, bool $lock): Collection
    {
        $query = DB::table('platform_transactions as pt')
            ->join('orders as o', 'o.id', '=', 'pt.order_id')
            ->whereNotNull('o.restaurant_id')
            ->where('pt.status', 'success')
            ->where('pt.settlement_status', 'pending')
            ->whereNull('pt.payout_id')
            ->whereBetween('pt.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($restaurantId, fn ($q) => $q->where('o.restaurant_id', $restaurantId))
            ->select('pt.id', 'pt.vendor_amount', 'pt.platform_commission', 'o.restaurant_id');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    /**
     * Take the restaurant-borne, already-paid refunds off each group's net.
     *
     * @param  Collection<int, array<string,mixed>> $groups
     * @return Collection<int, array<string,mixed>>  same groups plus refund_deduction and refund_ids
     */
    private function withRefunds(Collection $groups, bool $lock): Collection
    {
        if ($groups->isEmpty()) {
            return $groups;
        }

        $refunds = Refund::query()
            ->where('status', 'paid')
            ->where('bearer', 'vendor')
            ->whereNull('payout_id')
            ->where('vendor_deduction', '>', 0)
            ->whereIn('restaurant_id', $groups->pluck('restaurant_id')->all())
            ->orderBy('id')
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->get()
            ->groupBy('restaurant_id');

        return $groups->map(function (array $group) use ($refunds) {
            $net       = (float) $group['net'];
            $deducted  = 0.0;
            $refundIds = [];

            foreach ($refunds->get($group['restaurant_id'], collect()) as $refund) {
                $share = (float) $refund->vendor_deduction;

                // Never push the payout below zero; the rest waits.
                if (round($net - $deducted - $share, 2) < 0) {
                    break;
                }

                $deducted   += $share;
                $refundIds[] = (int) $refund->id;
            }

            $group['refund_deduction'] = round($deducted, 2);
            $group['refund_ids']       = $refundIds;
            $group['net']              = round($net - $deducted, 2);

            return $group;
        })->values();
    }

    /**
     * @param Collection<int, object> $rows
     * @return Collection<int, array{restaurant_id:int, orders:int, gross:float, commission:float, net:float, rate:float, transaction_ids:list<int>}>
     */
    private function summarize(Collection $rows): Collection
    {
        return $rows->groupBy('restaurant_id')->map(function (Collection $group, $restaurantId) {
            $net        = round((float) $group->sum(fn ($r) => (float) $r->vendor_amount), 2);
            $commission = round((float) $group->sum(fn ($r) => (float) $r->platform_commission), 2);
            $gross      = round($net + $commission, 2);

            return [
                'restaurant_id'   => (int) $restaurantId,
                'orders'          => $group->count(),
                'gross'           => $gross,
                'commission'      => $commission,
                'net'             => $net,
                // Average rate actually applied (rates may have changed during the period).
                'rate'            => $gross > 0 ? round($commission / $gross * 100, 2) : 0.0,
                'transaction_ids' => $group->pluck('id')->map(fn ($id) => (int) $id)->all(),
            ];
        })->values();
    }

    private function nextNumber(): string
    {
        do {
            $number = 'PO' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
        } while (Payout::query()->where('payout_number', $number)->exists());

        return $number;
    }

    private function notifyVendor(Payout $payout): void
    {
        try {
            $payout->loadMissing('restaurant');
            $ownerId = $payout->restaurant?->owner_id;

            if ($ownerId === null) {
                return;
            }

            $labels = ['bkash' => 'bKash', 'nagad' => 'Nagad', 'bank_transfer' => 'ব্যাংক', 'cash' => 'নগদ'];
            $method = $labels[$payout->payment_method] ?? (string) $payout->payment_method;
            $amount = '৳' . number_format((float) $payout->net_amount);
            $period = $payout->period_start->format('d M') . ' – ' . $payout->period_end->format('d M Y');
            $ref    = $payout->transaction_reference ? " রেফারেন্স: {$payout->transaction_reference}" : '';

            Notification::create([
                'user_id'    => $ownerId,
                'type'       => 'vendor_payout_paid',
                'title'      => 'পেআউট পাঠানো হয়েছে',
                'body'       => "{$period} এর {$amount} {$method}-এ পাঠানো হয়েছে।{$ref}",
                'data'       => ['payout_id' => $payout->id, 'amount' => $payout->net_amount],
                'action_url' => route('vendor.finances', [], false),
                'priority'   => 'high',
            ]);
        } catch (Throwable $e) {
            // A notification problem must never undo or break the payout action.
            Log::warning('Vendor payout notification failed', [
                'payout_id' => $payout->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
