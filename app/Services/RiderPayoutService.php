<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\RiderPayout;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Admin actions on a rider's payout request.
 *
 *   pending  -> approved            (approve)
 *   pending / approved -> rejected  (reject)        note required
 *   approved / processing -> paid   (markPaid)      transaction id required
 *   approved / processing -> failed (markFailed)    note required
 *
 * Every method returns false when the request is no longer in a state that
 * allows the action (for example another admin already handled it), so a
 * double click or two admins can never apply the same step twice.
 *
 * Rejected and failed requests stop counting against the rider's balance
 * (see RiderBalanceService), so that money becomes available again.
 */
class RiderPayoutService
{
    public function approve(int $payoutId, User $admin, ?string $note = null): bool
    {
        return $this->transition($payoutId, ['pending'], 'approved', $admin, [
            'admin_note' => $note,
        ], 'Admin approved rider payout', 'payout_approved');
    }

    public function reject(int $payoutId, User $admin, string $note): bool
    {
        return $this->transition($payoutId, ['pending', 'approved'], 'rejected', $admin, [
            'admin_note'  => $note,
            'resolved_at' => now(),
        ], 'Admin rejected rider payout', 'payout_rejected');
    }

    public function markPaid(int $payoutId, User $admin, string $transactionId, ?string $note = null): bool
    {
        return $this->transition($payoutId, ['approved', 'processing'], 'paid', $admin, [
            'transaction_id' => $transactionId,
            'admin_note'     => $note,
            'resolved_at'    => now(),
        ], 'Admin marked rider payout as paid', 'payout_paid');
    }

    public function markFailed(int $payoutId, User $admin, string $note): bool
    {
        return $this->transition($payoutId, ['approved', 'processing'], 'failed', $admin, [
            'admin_note'  => $note,
            'resolved_at' => now(),
        ], 'Admin marked rider payout as failed', 'payout_failed');
    }

    /**
     * @param list<string>         $allowedFrom
     * @param array<string, mixed> $extra        extra columns to write (null values keep the old value)
     */
    private function transition(
        int $payoutId,
        array $allowedFrom,
        string $to,
        User $admin,
        array $extra,
        string $activityText,
        string $notificationType
    ): bool {
        $done = null;

        $ok = DB::transaction(function () use ($payoutId, $allowedFrom, $to, $admin, $extra, $activityText, &$done) {
            $payout = RiderPayout::query()
                ->whereKey($payoutId)
                ->lockForUpdate()
                ->first();

            if ($payout === null || ! in_array($payout->status, $allowedFrom, true)) {
                return false;
            }

            $from = $payout->status;

            // status / processed_by / admin_note / transaction_id / resolved_at are
            // not mass-assignable on RiderPayout, so forceFill() is used on purpose.
            $payout->forceFill(array_merge(
                array_filter($extra, fn ($v) => $v !== null),
                ['status' => $to, 'processed_by' => $admin->id]
            ))->save();

            activity()
                ->causedBy($admin)
                ->performedOn($payout)
                ->withProperties(['from' => $from, 'to' => $to, 'amount' => $payout->amount])
                ->log($activityText);

            $done = $payout;

            return true;
        });

        // After the commit, so the rider is never told about a rolled-back change.
        if ($ok && $done !== null) {
            $this->notifyRider($done, $notificationType);
        }

        return $ok;
    }

    private function notifyRider(RiderPayout $payout, string $type): void
    {
        try {
            $amount = '৳' . number_format((float) $payout->amount);
            $method = $payout->method === 'bkash' ? 'bKash' : 'ব্যাংক';
            $note   = $payout->admin_note ? " কারণ: {$payout->admin_note}" : '';

            [$title, $body, $priority] = match ($type) {
                'payout_approved' => [
                    'পেআউট অনুমোদিত',
                    "আপনার {$amount} পেআউট অনুমোদিত হয়েছে। শীঘ্রই টাকা পাঠানো হবে।",
                    'normal',
                ],
                'payout_paid' => [
                    'টাকা পাঠানো হয়েছে',
                    "আপনার {$amount} {$method}-এ পাঠানো হয়েছে। ট্রানজেকশন আইডি: {$payout->transaction_id}",
                    'high',
                ],
                'payout_rejected' => [
                    'পেআউট বাতিল হয়েছে',
                    "আপনার {$amount} পেআউট রিকুয়েস্ট বাতিল হয়েছে।{$note} টাকা আপনার ব্যালেন্সে ফেরত এসেছে।",
                    'high',
                ],
                default => [
                    'পেআউট ব্যর্থ হয়েছে',
                    "আপনার {$amount} পাঠানো যায়নি।{$note} টাকা আপনার ব্যালেন্সে ফেরত এসেছে।",
                    'high',
                ],
            };

            Notification::create([
                'user_id'    => $payout->rider_id,
                'type'       => Str::limit($type, 60, ''),
                'title'      => Str::limit($title, 120, ''),
                'body'       => $body,
                'data'       => ['payout_id' => $payout->id, 'amount' => $payout->amount],
                'action_url' => route('rider.finance', [], false),
                'priority'   => $priority,
            ]);
        } catch (Throwable $e) {
            // A notification problem must never undo or break the payout action.
            Log::warning('Rider payout notification failed', [
                'payout_id' => $payout->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
