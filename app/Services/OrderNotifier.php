<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes in-app (database) notifications for order events.
 *
 * Rules:
 *  - A notification problem must NEVER break an order action. Every public
 *    method is wrapped so an error is only logged.
 *  - Call these AFTER the database transaction has committed, so nobody is
 *    told about something that was rolled back.
 *  - user_id = null means "all admins" (the admin bell already reads those).
 *
 * Who hears what:
 *   placed          -> vendor (new order), customer (order sent), admins (product orders)
 *   statusChanged   -> customer; "ready" also goes to online riders
 *   riderAccepted   -> customer, vendor
 *   pickedUp        -> customer
 *   delivered       -> customer, vendor
 *   cancelled       -> customer and vendor (whoever did not cancel it themselves)
 *   riderRemoved    -> the removed rider, then the order is offered to riders again
 *   riderReleased   -> the order is offered to riders again
 *   deliveryIssueReported -> admins + customer ("keep your phone on")
 *   deliveryIssueCleared  -> the rider (admin asked to retry)
 *   deliveryFailed        -> customer, vendor, rider
 */
class OrderNotifier
{
    /** Max riders told about one available order, so one order never writes thousands of rows. */
    private const MAX_RIDERS_PER_OFFER = 200;

    /** Types that go to ALL admins (user_id = null). Everything else needs a real user. */
    private const BROADCAST_TYPES = ['new_product_order', 'delivery_issue'];

    public function placed(Order $order): void
    {
        $this->safely('placed', function () use ($order) {
            $number = $this->number($order);

            $this->send(
                $order->customer_id,
                'order_placed',
                'অর্ডার পাঠানো হয়েছে',
                "আপনার অর্ডার {$number} পাঠানো হয়েছে। রেস্টুরেন্টের নিশ্চিতকরণের অপেক্ষায়।",
                $this->trackUrl($order),
                'normal',
                $order
            );

            if ($order->isVendorOrder()) {
                $this->send(
                    $this->vendorId($order),
                    'new_order',
                    "নতুন অর্ডার {$number}",
                    'একটি নতুন অর্ডার এসেছে। দ্রুত নিশ্চিত করুন।',
                    $this->vendorUrl(),
                    'high',
                    $order
                );
            } else {
                // Store (product) order: tell all admins.
                $this->send(
                    null,
                    'new_product_order',
                    "নতুন প্রোডাক্ট অর্ডার {$number}",
                    'একটি নতুন স্টোর অর্ডার এসেছে।',
                    $this->adminUrl(),
                    'high',
                    $order
                );
            }
        });
    }

    /** Forward status changes done by vendor or admin (confirmed, preparing, ready). */
    public function statusChanged(Order $order, string $from, string $to): void
    {
        $this->safely('statusChanged', function () use ($order, $to) {
            $number = $this->number($order);

            $text = match ($to) {
                'confirmed' => ['অর্ডার নিশ্চিত হয়েছে', "{$number} রেস্টুরেন্ট নিশ্চিত করেছে।"],
                'preparing' => ['খাবার তৈরি হচ্ছে', "{$number} এর খাবার তৈরি হচ্ছে।"],
                'ready'     => ['খাবার প্রস্তুত', "{$number} প্রস্তুত। রাইডার খোঁজা হচ্ছে।"],
                default     => null,
            };

            if ($text === null) {
                return;
            }

            $this->send(
                $order->customer_id,
                'order_' . $to,
                $text[0],
                $text[1],
                $this->trackUrl($order),
                'normal',
                $order
            );

            if ($to === 'ready') {
                $this->offerToRiders($order);
            }
        });
    }

    public function riderAccepted(Order $order): void
    {
        $this->safely('riderAccepted', function () use ($order) {
            $number    = $this->number($order);
            $riderName = $order->rider?->name ?? 'রাইডার';

            $this->send(
                $order->customer_id,
                'rider_assigned',
                'রাইডার ঠিক হয়েছে',
                "{$riderName} আপনার অর্ডার {$number} নিয়ে আসবেন।",
                $this->trackUrl($order),
                'normal',
                $order
            );

            $this->send(
                $this->vendorId($order),
                'rider_assigned',
                "রাইডার আসছে {$number}",
                "{$riderName} খাবার নিতে আসছেন।",
                $this->vendorUrl(),
                'normal',
                $order
            );
        });
    }

    public function pickedUp(Order $order): void
    {
        $this->safely('pickedUp', function () use ($order) {
            $number = $this->number($order);

            $this->send(
                $order->customer_id,
                'order_picked_up',
                'অর্ডার পথে আছে',
                "{$number} রাইডার নিয়ে রওনা দিয়েছেন।",
                $this->trackUrl($order),
                'normal',
                $order
            );
        });
    }

    public function delivered(Order $order): void
    {
        $this->safely('delivered', function () use ($order) {
            $number = $this->number($order);

            $this->send(
                $order->customer_id,
                'order_delivered',
                'অর্ডার পৌঁছে গেছে',
                "{$number} পৌঁছে গেছে। ধন্যবাদ!",
                $this->trackUrl($order),
                'normal',
                $order
            );

            $this->send(
                $this->vendorId($order),
                'order_delivered',
                "অর্ডার ডেলিভারি হয়েছে {$number}",
                'অর্ডারটি কাস্টমারের কাছে পৌঁছে গেছে।',
                $this->vendorUrl(),
                'low',
                $order
            );
        });
    }

    /**
     * Cancelled by customer, vendor, admin or the system ($actor = null).
     * The person who cancelled is not told about their own action.
     */
    public function cancelled(Order $order, ?User $actor): void
    {
        $this->safely('cancelled', function () use ($order, $actor) {
            $number = $this->number($order);
            $reason = trim((string) $order->cancel_reason);
            $suffix = $reason !== '' ? " কারণ: {$reason}" : '';

            if ($actor?->id !== $order->customer_id) {
                $this->send(
                    $order->customer_id,
                    'order_cancelled',
                    'অর্ডার বাতিল হয়েছে',
                    "আপনার অর্ডার {$number} বাতিল হয়েছে।{$suffix}",
                    $this->trackUrl($order),
                    'high',
                    $order
                );
            }

            $vendorId = $this->vendorId($order);

            if ($vendorId !== null && $actor?->id !== $vendorId) {
                $this->send(
                    $vendorId,
                    'order_cancelled',
                    "অর্ডার বাতিল {$number}",
                    "অর্ডারটি বাতিল হয়েছে।{$suffix}",
                    $this->vendorUrl(),
                    'high',
                    $order
                );
            }
        });
    }

    /**
     * A rider was taken off the order by an admin or by the pickup timeout.
     * Tell that rider, then offer the order to all online riders again.
     */
    public function riderRemoved(Order $order, int $riderId, ?User $actor): void
    {
        $this->safely('riderRemoved', function () use ($order, $riderId, $actor) {
            $number = $this->number($order);

            $this->send(
                $riderId,
                'rider_unassigned',
                "অর্ডার সরানো হয়েছে {$number}",
                $actor === null
                    ? 'সময়মতো খাবার না নেওয়ায় অর্ডারটি আপনার কাছ থেকে সরানো হয়েছে।'
                    : 'অ্যাডমিন অর্ডারটি আপনার কাছ থেকে সরিয়ে নিয়েছেন।',
                $this->riderUrl(),
                'high',
                $order
            );

            $this->offerToRiders($order, exceptRiderId: $riderId);
        });
    }

    /** The rider gave the order back himself: just offer it to the others again. */
    public function riderReleased(Order $order, int $riderId): void
    {
        $this->safely('riderReleased', function () use ($order, $riderId) {
            $this->offerToRiders($order, exceptRiderId: $riderId);
        });
    }

    /** Rider cannot hand over a picked-up order: admins must decide, customer is warned. */
    public function deliveryIssueReported(Order $order): void
    {
        $this->safely('deliveryIssueReported', function () use ($order) {
            $number = $this->number($order);
            $reason = trim((string) $order->delivery_issue_reason);

            $this->send(
                null,
                'delivery_issue',
                "ডেলিভারি সমস্যা {$number}",
                $reason !== '' ? "রাইডার জানিয়েছেন: {$reason}" : 'রাইডার অর্ডারটি পৌঁছে দিতে পারছেন না।',
                $this->adminUrl(),
                'high',
                $order
            );

            $this->send(
                $order->customer_id,
                'delivery_issue_customer',
                'ডেলিভারিতে সমস্যা হচ্ছে',
                "{$number} পৌঁছে দিতে রাইডারের সমস্যা হচ্ছে। অনুগ্রহ করে ফোন চালু রাখুন।",
                $this->trackUrl($order),
                'high',
                $order
            );
        });
    }

    /** Admin asked the rider to try again. */
    public function deliveryIssueCleared(Order $order): void
    {
        $this->safely('deliveryIssueCleared', function () use ($order) {
            $this->send(
                $order->rider_id !== null ? (int) $order->rider_id : null,
                'delivery_retry',
                'আবার ডেলিভারির চেষ্টা করুন',
                "অ্যাডমিন {$this->number($order)} আবার ডেলিভারির চেষ্টা করতে বলেছেন।",
                $this->riderUrl(),
                'high',
                $order
            );
        });
    }

    /** Admin marked a picked-up order as failed (order becomes cancelled). */
    public function deliveryFailed(Order $order): void
    {
        $this->safely('deliveryFailed', function () use ($order) {
            $number = $this->number($order);
            $reason = trim((string) $order->cancel_reason);
            $suffix = $reason !== '' ? " {$reason}" : '';

            $this->send(
                $order->customer_id,
                'order_cancelled',
                'ডেলিভারি সম্পন্ন হয়নি',
                "আপনার অর্ডার {$number} ডেলিভারি করা যায়নি, তাই বাতিল হয়েছে।{$suffix}",
                $this->trackUrl($order),
                'high',
                $order
            );

            $this->send(
                $this->vendorId($order),
                'order_cancelled',
                "ডেলিভারি ব্যর্থ {$number}",
                "অর্ডারটি ডেলিভারি করা যায়নি।{$suffix}",
                $this->vendorUrl(),
                'high',
                $order
            );

            $this->send(
                $order->rider_id !== null ? (int) $order->rider_id : null,
                'delivery_failed',
                "ডেলিভারি ব্যর্থ {$number}",
                'অ্যাডমিন অর্ডারটি ডেলিভারি ব্যর্থ হিসেবে চিহ্নিত করেছেন।',
                $this->riderUrl(),
                'normal',
                $order
            );
        });
    }

    /* ───────────────────────── internals ───────────────────────── */

    /** "Order ready, come and take it" for every online, approved rider. */
    private function offerToRiders(Order $order, ?int $exceptRiderId = null): void
    {
        $riderIds = User::query()
            ->where('role', 'rider')
            ->where('is_active', true)
            ->when($exceptRiderId !== null, fn ($q) => $q->where('id', '!=', $exceptRiderId))
            ->whereHas('riderProfile', function ($q) {
                $q->where('is_approved', true)->where('is_online', true);
            })
            ->limit(self::MAX_RIDERS_PER_OFFER)
            ->pluck('id');

        if ($riderIds->isEmpty()) {
            return;
        }

        $number = $this->number($order);
        $now    = now();
        $data   = json_encode($this->data($order));

        // One insert for all riders. insert() skips model events and does not
        // cast, so created_at/updated_at and the JSON are set by hand.
        $rows = $riderIds->map(fn ($id) => [
            'user_id'    => $id,
            'type'       => 'order_available',
            'title'      => 'নতুন ডেলিভারি পাওয়া যাচ্ছে',
            'body'       => "অর্ডার {$number} প্রস্তুত। নিতে চাইলে এখনই অ্যাকসেপ্ট করুন।",
            'data'       => $data,
            'action_url' => $this->riderUrl(),
            'priority'   => 'high',
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        Notification::query()->insert($rows);
    }

    private function send(
        ?int $userId,
        string $type,
        string $title,
        string $body,
        ?string $url,
        string $priority,
        Order $order
    ): void {
        // user_id = null is a valid "all admins" notification. For every other
        // case a missing user (for example an order without a vendor) is skipped.
        if ($userId === null && ! in_array($type, self::BROADCAST_TYPES, true)) {
            return;
        }

        Notification::create([
            'user_id'    => $userId,
            'type'       => Str::limit($type, 60, ''),
            'title'      => Str::limit($title, 120, ''),
            'body'       => $body,
            'data'       => $this->data($order),
            'action_url' => $url,
            'priority'   => $priority,
        ]);
    }

    /** @return array<string,mixed> */
    private function data(Order $order): array
    {
        return [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
        ];
    }

    private function number(Order $order): string
    {
        return '#' . $order->order_number;
    }

    private function vendorId(Order $order): ?int
    {
        if (! $order->isVendorOrder() || $order->restaurant_id === null) {
            return null;
        }

        $ownerId = $order->restaurant?->owner_id;

        return $ownerId !== null ? (int) $ownerId : null;
    }

    // Relative URLs (no host), so a wrong APP_URL can never break the links.
    private function trackUrl(Order $order): string
    {
        return route('customer.track', ['orderId' => $order->id], false);
    }

    private function vendorUrl(): string
    {
        return route('vendor.orders.live', [], false);
    }

    private function riderUrl(): string
    {
        return route('rider.delivery.ongoing', [], false);
    }

    private function adminUrl(): string
    {
        return route('admin.orders', [], false);
    }

    private function safely(string $event, callable $work): void
    {
        try {
            $work();
        } catch (Throwable $e) {
            Log::warning('Order notification failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
