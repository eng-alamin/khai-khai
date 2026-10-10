<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\AdminSetting;
use App\Models\Food;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Cart pricing + order placement for the mobile app.
 *
 * Port of Livewire\Customer\CartComponent (pricing() and processFoodOrder()).
 * The app only sends item ids + quantities (+ an optional coupon code); every
 * name, price, availability and fee is read fresh from the database here.
 *
 * Scope: restaurant FOOD orders. Admin "product" orders are a separate step.
 */
class CheckoutService
{
    public const MAX_QTY_PER_ITEM = 20;

    public function __construct(
        private readonly DeliveryChargeService $delivery,
        private readonly CouponService $coupons,
        private readonly OrderSnapshotService $snapshots,
        private readonly OrderNotifier $notifier,
    ) {
    }

    // ─────────────────────────────────────────
    // QUOTE  (POST /cart/quote) — writes nothing
    // ─────────────────────────────────────────

    /**
     * @param  array<int, array{id:int, qty:int}> $rawItems
     * @return array<string, mixed>
     * @throws CheckoutException
     */
    public function quote(User $user, array $rawItems, ?string $couponCode, bool $applyingCoupon = false): array
    {
        $cart = $this->loadCart($rawItems);
        $address = $this->addressFor($user);
        $fee = $this->deliveryFor($cart['restaurant'], $address);

        $subtotal = $cart['subtotal'];
        $discount = 0.0;
        $couponError = null;
        $appliedCode = null;

        $couponCode = trim((string) $couponCode);

        if ($couponCode !== '') {
            // Only an explicit "Apply" tap counts toward the guess limit;
            // re-pricing an already applied coupon on every cart change does not.
            if ($applyingCoupon && $this->tooManyCouponTries($user)) {
                $couponError = 'অনেকবার চেষ্টা করেছেন, কিছুক্ষণ পরে আবার চেষ্টা করুন।';
            } else {
                $result = $this->coupons->evaluate(
                    $couponCode, $user, (int) $cart['restaurant']->id, $subtotal, $fee['fee']
                );

                if ($result['error'] === null) {
                    $discount = $result['discount'];
                    $appliedCode = $result['coupon']->code;
                } else {
                    $couponError = $result['error'];
                }
            }
        }

        $problems = $this->orderProblems($cart['restaurant'], $subtotal, $fee['distance_km'], $address !== null);

        return [
            'restaurant_id'   => (int) $cart['restaurant']->id,
            'restaurant_name' => $cart['restaurant']->name,
            'items'           => array_values($cart['lines']),
            'subtotal'        => $subtotal,
            'delivery_fee'    => $fee['fee'],
            'distance_km'     => $fee['distance_km'],
            'discount'        => $discount,
            'total'           => max(0.0, round($subtotal + $fee['fee'] - $discount, 2)),
            'coupon_code'     => $appliedCode,
            'coupon_error'    => $couponError,
            'can_order'       => $problems === [],
            'problems'        => $problems,
        ];
    }

    // ─────────────────────────────────────────
    // PLACE  (POST /orders)
    // ─────────────────────────────────────────

    /**
     * @param  array<int, array{id:int, qty:int}> $rawItems
     * @throws CheckoutException
     */
    public function place(
        User $user,
        array $rawItems,
        ?string $couponCode,
        ?string $instructions,
        string $platform,
    ): Order {
        $key = 'place-order-api:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw new CheckoutException(
                'অনেকবার অর্ডার করার চেষ্টা করেছেন, কিছুক্ষণ পরে আবার চেষ্টা করুন।',
                'too_many_attempts'
            );
        }
        RateLimiter::hit($key, 600);

        // One checkout at a time per customer (double tap / retry / second device).
        $lock = Cache::lock('place-order:' . $user->id, 15);

        if (! $lock->get()) {
            throw new CheckoutException(
                'আপনার আগের অর্ডারটি প্রসেস হচ্ছে, একটু অপেক্ষা করুন।',
                'order_in_progress'
            );
        }

        try {
            return $this->process($user, $rawItems, $couponCode, $instructions, $platform);
        } finally {
            $lock->release();
        }
    }

    private function process(User $user, array $rawItems, ?string $couponCode, ?string $instructions, string $platform): Order
    {
        $cart = $this->loadCart($rawItems);
        $restaurant = $cart['restaurant'];

        $address = $this->addressFor($user);
        $fee = $this->deliveryFor($restaurant, $address);
        $subtotal = $cart['subtotal'];

        $problems = $this->orderProblems($restaurant, $subtotal, $fee['distance_km'], $address !== null);

        if ($problems !== []) {
            throw new CheckoutException($problems[0]['message'], $problems[0]['code']);
        }

        $couponCode = trim((string) $couponCode);

        // Never silently charge full price when the coupon went bad.
        if ($couponCode !== '') {
            $check = $this->coupons->evaluate($couponCode, $user, (int) $restaurant->id, $subtotal, $fee['fee']);

            if ($check['error'] !== null) {
                throw new CheckoutException($check['error'], 'coupon_invalid');
            }
        }

        do {
            $orderNumber = 'KK' . now()->format('ymd') . strtoupper(Str::random(4));
        } while (Order::where('order_number', $orderNumber)->exists());

        try {
            $order = DB::transaction(function () use (
                $user, $cart, $restaurant, $address, $fee, $subtotal,
                $couponCode, $instructions, $platform, $orderNumber
            ) {
                $coupon = null;
                $discount = 0.0;

                if ($couponCode !== '') {
                    // Row lock: limits cannot be beaten by two simultaneous checkouts.
                    $result = $this->coupons->evaluate(
                        $couponCode, $user, (int) $restaurant->id, $subtotal, $fee['fee'], lock: true
                    );

                    if ($result['error'] !== null) {
                        throw new CheckoutException($result['error'], 'coupon_invalid');
                    }

                    $coupon = $result['coupon'];
                    $discount = $result['discount'];
                }

                $total = max(0.0, round($subtotal + $fee['fee'] - $discount, 2));
                $note = $instructions !== null ? trim($instructions) : '';

                $order = Order::create([
                    'order_number'               => $orderNumber,
                    'order_type'                 => Order::TYPE_VENDOR,
                    'customer_id'                => $user->id,
                    'restaurant_id'              => $restaurant->id,
                    'restaurant_snapshot'        => $this->snapshots->restaurant($restaurant),
                    'customer_snapshot'          => $this->snapshots->customer($user),
                    'coupon_snapshot'            => $coupon ? $this->snapshots->coupon($coupon, $discount) : null,
                    'rider_id'                   => null,
                    'delivery_address_id'        => $address->id,
                    'delivery_address_snapshot'  => [
                        'label'        => $address->label,
                        'full_address' => $address->full_address,
                        'city'         => $address->city,
                        'postal_code'  => $address->postal_code,
                        'latitude'     => $address->latitude,
                        'longitude'    => $address->longitude,
                    ],
                    'delivery_distance_km'       => $fee['distance_km'],
                    'coupon_id'                  => $coupon?->id,
                    'payment_method'             => 'cash_on_delivery',
                    'special_instructions'       => $note !== '' ? $note : null,
                    'estimated_delivery_minutes' => 45,
                    'order_source'               => $platform,
                ]);

                // status / pricing / payment_status are not mass-assignable.
                $order->forceFill([
                    'status'                => 'pending',
                    'subtotal'              => $subtotal,
                    'delivery_fee'          => $fee['fee'],
                    'discount_amount'       => $discount,
                    'total_amount'          => $total,
                    'payment_status'        => 'pending',
                    'estimated_delivery_at' => now()->addMinutes(45),
                ])->save();

                $rows = [];
                foreach ($cart['lines'] as $line) {
                    $rows[] = [
                        'order_id'        => $order->id,
                        'orderable_type'  => Food::class,
                        'orderable_id'    => $line['id'],
                        'item_image'      => $line['image_url'],
                        'item_name'       => $line['name'],
                        'item_price'      => $line['price'],
                        'quantity'        => $line['qty'],
                        'discount_amount' => 0,
                        'line_total'      => $line['line_total'],
                        'emoji'           => $line['emoji'],
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }
                OrderItem::insert($rows);

                if ($coupon) {
                    $this->coupons->redeem($coupon, $order, $user, $discount);
                }

                OrderStatusLog::create([
                    'order_id'     => $order->id,
                    'from_status'  => null,
                    'to_status'    => 'pending',
                    'changed_by'   => $user->id,
                    'changed_type' => 'customer',
                    'note'         => 'Customer placed order (' . $platform . ' app)',
                ]);

                $user->customerProfile()->increment('total_orders');

                activity()
                    ->performedOn($order)
                    ->causedBy($user)
                    ->withProperties([
                        'order_number'         => $order->order_number,
                        'total_amount'         => $order->total_amount,
                        'delivery_fee'         => $order->delivery_fee,
                        'discount_amount'      => $order->discount_amount,
                        'coupon_code'          => $coupon?->code,
                        'delivery_distance_km' => $order->delivery_distance_km,
                        'order_source'         => $platform,
                    ])
                    ->log('Order placed');

                return $order;
            });
        } catch (CheckoutException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('API order placement failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // Internal error text must never reach the customer.
            throw new CheckoutException('অর্ডার দেওয়া যায়নি, আবার চেষ্টা করুন।', 'server_error');
        }

        // Committed: safe to notify (OrderNotifier swallows its own failures).
        $this->notifier->placed($order);

        return $order;
    }

    // ─────────────────────────────────────────
    // SHARED PIECES
    // ─────────────────────────────────────────

    /**
     * Normalise ids/quantities, re-read everything from the DB and run the
     * checks that make the cart impossible to order at all.
     *
     * @param  array<int, array{id:int, qty:int}> $rawItems
     * @return array{restaurant: Restaurant, lines: array<int, array<string, mixed>>, subtotal: float}
     * @throws CheckoutException
     */
    private function loadCart(array $rawItems): array
    {
        // Same food twice => one line. Quantity stays between 1 and the max.
        $wanted = [];
        foreach ($rawItems as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $wanted[$id] = min(self::MAX_QTY_PER_ITEM, ($wanted[$id] ?? 0) + max(1, (int) ($row['qty'] ?? 1)));
        }

        if ($wanted === []) {
            throw new CheckoutException('আগে কার্টে আইটেম যোগ করুন।', 'cart_empty');
        }

        $foods = Food::query()->whereIn('id', array_keys($wanted))->get()->keyBy('id');

        $gone = [];
        foreach ($wanted as $id => $qty) {
            $food = $foods->get($id);
            if (! $food || ! $food->is_available) {
                $gone[$id] = $food?->name ?? ('#' . $id);
            }
        }

        if ($gone !== []) {
            throw new CheckoutException(
                'কিছু আইটেম আর পাওয়া যাচ্ছে না: ' . implode(', ', $gone),
                'items_unavailable',
                ['unavailable_ids' => array_keys($gone)]
            );
        }

        // One restaurant per order — judged by the database, not by the app.
        $restaurantIds = $foods->pluck('restaurant_id')->unique();

        if ($restaurantIds->count() > 1) {
            throw new CheckoutException(
                'একটি অর্ডারে শুধু একটি রেস্টুরেন্টের আইটেম থাকতে পারে।',
                'multiple_restaurants'
            );
        }

        $restaurant = Restaurant::with('settings')->find($restaurantIds->first());

        if (! $restaurant || ! $restaurant->is_active || ! $restaurant->is_approved) {
            throw new CheckoutException('এই রেস্টুরেন্ট থেকে এখন অর্ডার নেওয়া সম্ভব নয়।', 'restaurant_unavailable');
        }

        if (! $restaurant->is_open) {
            throw new CheckoutException('রেস্টুরেন্টটি এখন বন্ধ, দয়া করে পরে চেষ্টা করুন।', 'restaurant_closed');
        }

        $lines = [];
        $subtotal = 0.0;

        foreach ($wanted as $id => $qty) {
            $food = $foods->get($id);
            $price = (float) $food->price;
            $lineTotal = round($price * $qty, 2);
            $subtotal += $lineTotal;

            $lines[$id] = [
                'id'         => $food->id,
                'name'       => $food->name,
                'price'      => $price,
                'qty'        => $qty,
                'emoji'      => $food->emoji,
                'image_url'  => $food->image_url,
                'line_total' => $lineTotal,
            ];
        }

        return ['restaurant' => $restaurant, 'lines' => $lines, 'subtotal' => round($subtotal, 2)];
    }

    private function addressFor(User $user)
    {
        return $user->addresses()->where('is_default', true)->first()
            ?? $user->addresses()->latest()->first();
    }

    /** @return array{distance_km: float|null, fee: float} */
    private function deliveryFor(Restaurant $restaurant, $address): array
    {
        $fallback = (float) AdminSetting::get('fallback_delivery_fee', 49);

        return $this->delivery->calculateWithDistance(
            originLat: $restaurant->latitude,
            originLng: $restaurant->longitude,
            destLat: $address?->latitude,
            destLng: $address?->longitude,
            fallbackFee: $fallback,
        );
    }

    /**
     * Rules that stop an order but are not the cart's fault, so the quote can
     * show them while the customer is still deciding.
     *
     * @return array<int, array{code: string, message: string}>
     */
    private function orderProblems(Restaurant $restaurant, float $subtotal, ?float $distanceKm, bool $hasAddress): array
    {
        $problems = [];

        if (! $hasAddress) {
            $problems[] = [
                'code'    => 'address_required',
                'message' => 'অর্ডার করতে আগে একটি ডেলিভারি ঠিকানা যোগ করুন।',
            ];
        }

        // Vendor-set minimum (food subtotal only, before fee and discount).
        $min = (float) ($restaurant->settings?->min_order_amount ?? 0);

        if ($min > 0 && $subtotal < $min) {
            $problems[] = [
                'code'    => 'below_min_order',
                'message' => 'এই রেস্টুরেন্টের সর্বনিম্ন অর্ডার ৳' . number_format($min, 0)
                    . '। আরও ৳' . number_format($min - $subtotal, 0) . ' এর আইটেম যোগ করুন।',
            ];
        }

        // 0 = no limit. Unknown distance (address without a pin) is not blocked.
        $maxKm = (float) AdminSetting::get('max_delivery_distance_km', 0);

        if ($maxKm > 0 && $distanceKm !== null && $distanceKm > $maxKm) {
            $problems[] = [
                'code'    => 'too_far',
                'message' => 'আপনার ঠিকানা এই রেস্টুরেন্ট থেকে ' . number_format($distanceKm, 1)
                    . ' কিমি দূরে। সর্বোচ্চ ' . rtrim(rtrim(number_format($maxKm, 1), '0'), '.')
                    . ' কিমি পর্যন্ত ডেলিভারি দেওয়া হয়।',
            ];
        }

        return $problems;
    }

    private function tooManyCouponTries(User $user): bool
    {
        $key = 'coupon-apply:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return true;
        }

        RateLimiter::hit($key, 60);

        return false;
    }
}
