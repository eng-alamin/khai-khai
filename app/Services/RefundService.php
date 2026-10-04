<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\PlatformTransaction;
use App\Models\Refund;
use App\Models\SupportTicket;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Refunds of delivered orders.
 *
 * Rules:
 *  - Only delivered orders whose cash was collected (payment_status paid).
 *  - Several partial refunds are allowed; pending + paid ones together can
 *    never pass the order total (the order row is locked while checking).
 *  - bearer "vendor": the restaurant carries its share of the food money,
 *    taken off its next payout (VendorPayoutService). The commission part
 *    and anything above the food subtotal (delivery fee) stay with the platform.
 *  - Paying a refund does not touch the order's platform transaction, so the
 *    vendor's normal payout is never affected. Only a refund that adds up to
 *    the full order total flips orders.payment_status to "refunded".
 *
 * create() throws DomainException with an admin-facing message.
 */
class RefundService
{
    /** What is still refundable on an order (pending + paid refunds count). */
    public function remaining(Order $order): float
    {
        $taken = (float) Refund::query()
            ->where('order_id', $order->id)
            ->whereIn('status', ['pending', 'paid'])
            ->sum('amount');

        return round(max(0.0, (float) $order->total_amount - $taken), 2);
    }

    /** The restaurant's share of a refund, in taka. */
    public function vendorShare(Order $order, float $amount): float
    {
        $tx = PlatformTransaction::query()->where('order_id', $order->id)->first();

        if ($tx === null) {
            return 0.0;
        }

        // vendor_amount + platform_commission = the food subtotal of the order.
        $base = (float) $tx->vendor_amount + (float) $tx->platform_commission;

        if ($base <= 0) {
            return 0.0;
        }

        return round(min($amount, $base) * (float) $tx->vendor_amount / $base, 2);
    }

    public function create(
        User $admin,
        string $orderNumber,
        float $amount,
        string $reason,
        string $bearer,
        ?string $note = null,
        ?string $ticketNumber = null
    ): Refund {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new DomainException('The refund amount must be more than zero.');
        }
        if (! array_key_exists($reason, Refund::REASONS)) {
            throw new DomainException('Please choose a valid reason.');
        }
        if (! in_array($bearer, Refund::BEARERS, true)) {
            throw new DomainException('Please choose who carries the cost.');
        }

        $clean = strtoupper(ltrim(trim($orderNumber), '#'));

        if ($clean === '') {
            throw new DomainException('Enter the order number.');
        }

        $refund = DB::transaction(function () use ($admin, $clean, $amount, $reason, $bearer, $note, $ticketNumber) {
            $order = Order::query()
                ->whereRaw('UPPER(order_number) = ?', [$clean])
                ->lockForUpdate()
                ->first();

            if ($order === null) {
                throw new DomainException('No order with that number.');
            }
            if ($order->status !== 'delivered' || $order->payment_status !== 'paid') {
                throw new DomainException('Only delivered orders whose payment was collected can be refunded.');
            }

            $remaining = $this->remaining($order);

            if ($amount > $remaining) {
                throw new DomainException('At most ৳' . number_format($remaining, 2) . ' can still be refunded on this order.');
            }

            $deduction = 0.0;

            if ($bearer === 'vendor') {
                if ($order->restaurant_id === null) {
                    throw new DomainException('This order has no restaurant, so the platform must carry the cost.');
                }

                $deduction = $this->vendorShare($order, $amount);
            }

            $ticketId = null;

            if ($ticketNumber !== null && trim($ticketNumber) !== '') {
                $ticketId = SupportTicket::query()
                    ->where('ticket_number', trim($ticketNumber))
                    ->where('customer_id', $order->customer_id)
                    ->value('id');

                if ($ticketId === null) {
                    throw new DomainException('That ticket number does not belong to this order\'s customer.');
                }
            }

            $refund = new Refund();
            $refund->forceFill([
                'refund_number'     => $this->nextNumber(),
                'order_id'          => $order->id,
                'customer_id'       => $order->customer_id,
                'restaurant_id'     => $order->restaurant_id,
                'support_ticket_id' => $ticketId,
                'amount'            => $amount,
                'reason'            => $reason,
                'note'              => $note !== null && trim($note) !== '' ? mb_substr(trim($note), 0, 1000) : null,
                'bearer'            => $bearer,
                'vendor_deduction'  => $deduction,
                'status'            => 'pending',
                'created_by'        => $admin->id,
            ])->save();

            activity()
                ->causedBy($admin)
                ->performedOn($refund)
                ->withProperties([
                    'order'            => $order->order_number,
                    'amount'           => $amount,
                    'bearer'           => $bearer,
                    'vendor_deduction' => $deduction,
                ])
                ->log('Admin created a refund');

            return $refund;
        });

        return $refund;
    }

    /** Money was sent to the customer. false = not pending any more. */
    public function markPaid(int $refundId, User $admin, string $method, ?string $reference): bool
    {
        if (! in_array($method, Refund::METHODS, true)) {
            return false;
        }

        $paid = null;

        $ok = DB::transaction(function () use ($refundId, $admin, $method, $reference, &$paid) {
            $refund = Refund::query()->whereKey($refundId)->lockForUpdate()->first();

            if ($refund === null || $refund->status !== 'pending') {
                return false;
            }

            $order = Order::query()->whereKey($refund->order_id)->lockForUpdate()->first();

            $refund->forceFill([
                'status'       => 'paid',
                'method'       => $method,
                'reference'    => $reference !== null && trim($reference) !== '' ? mb_substr(trim($reference), 0, 100) : null,
                'paid_at'      => now(),
                'processed_by' => $admin->id,
            ])->save();

            // Everything of the order refunded -> the order itself reads "refunded".
            if ($order !== null) {
                $paidTotal = (float) Refund::query()
                    ->where('order_id', $order->id)
                    ->where('status', 'paid')
                    ->sum('amount');

                if ($paidTotal >= (float) $order->total_amount) {
                    $order->forceFill(['payment_status' => 'refunded'])->save();
                }
            }

            activity()
                ->causedBy($admin)
                ->performedOn($refund)
                ->withProperties(['method' => $method, 'reference' => $reference, 'amount' => $refund->amount])
                ->log('Admin marked a refund as paid');

            $paid = $refund;

            return true;
        });

        if ($ok && $paid !== null) {
            $this->notifyCustomer($paid);
        }

        return $ok;
    }

    /** Decided against it before sending. false = not pending any more. */
    public function cancel(int $refundId, User $admin, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($refundId, $admin, $reason) {
            $refund = Refund::query()->whereKey($refundId)->lockForUpdate()->first();

            if ($refund === null || $refund->status !== 'pending') {
                return false;
            }

            $refund->forceFill([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
                'processed_by' => $admin->id,
            ])->save();

            activity()
                ->causedBy($admin)
                ->performedOn($refund)
                ->withProperties(['reason' => $reason])
                ->log('Admin cancelled a refund');

            return true;
        });
    }

    private function nextNumber(): string
    {
        do {
            $number = 'RF' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
        } while (Refund::query()->where('refund_number', $number)->exists());

        return $number;
    }

    private function notifyCustomer(Refund $refund): void
    {
        try {
            $refund->loadMissing('order');

            $labels = ['bkash' => 'bKash', 'nagad' => 'Nagad', 'bank_transfer' => 'ব্যাংক', 'cash' => 'নগদ'];
            $method = $labels[$refund->method] ?? (string) $refund->method;
            $amount = '৳' . number_format((float) $refund->amount);
            $number = $refund->order?->order_number ?? '';

            Notification::create([
                'user_id'    => $refund->customer_id,
                'type'       => 'refund_paid',
                'title'      => 'রিফান্ড পাঠানো হয়েছে',
                'body'       => "{$number}: {$amount} {$method}-এ ফেরত পাঠানো হয়েছে।",
                'data'       => ['refund_id' => $refund->id, 'amount' => $refund->amount],
                'action_url' => route('customer.support', [], false),
                'priority'   => 'high',
            ]);
        } catch (Throwable $e) {
            // A notification problem must never undo or break the refund.
            Log::warning('Refund notification failed', [
                'refund_id' => $refund->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
