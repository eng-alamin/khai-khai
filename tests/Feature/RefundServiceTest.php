<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Order;
use App\Models\Payout;
use App\Models\PlatformTransaction;
use App\Models\Refund;
use App\Models\Restaurant;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\RefundService;
use App\Services\VendorPayoutService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplaceData;
use Tests\TestCase;

/**
 * Refunds of delivered orders, and how the restaurant's share reaches its payout.
 *
 * Fixture order: subtotal 500, delivery fee 40, total 540; commission 50, vendor 450.
 */
class RefundServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMarketplaceData;

    private function service(): RefundService
    {
        return app(RefundService::class);
    }

    private function deliveredOrder(?Restaurant $restaurant = null, ?User $customer = null, bool $withMoneyRecord = true): Order
    {
        $restaurant ??= $this->makeRestaurant();
        $customer   ??= $this->makeUser('customer');

        $order = $this->makeOrder($restaurant, $customer, 'delivered', ['payment_status' => 'paid']);

        if ($withMoneyRecord) {
            PlatformTransaction::forceCreate([
                'order_id'            => $order->id,
                'vendor_amount'       => 450,
                'rider_amount'        => 40,
                'platform_commission' => 50,
                'commission_rate'     => 10,
                'gateway'             => 'cod',
                'status'              => 'success',
                'settlement_status'   => 'pending',
            ]);
        }

        return $order;
    }

    private function refundFor(Order $order, float $amount, string $bearer = 'vendor'): Refund
    {
        return $this->service()->create($this->makeUser('admin'), $order->order_number, $amount, 'food_quality', $bearer);
    }

    // ── creating ─────────────────────────────────────────────

    public function test_a_vendor_refund_records_the_restaurants_share(): void
    {
        $order = $this->deliveredOrder();

        $refund = $this->refundFor($order, 100);

        $this->assertSame('pending', $refund->status);
        $this->assertEquals(100.00, (float) $refund->amount);
        // 100 of the food money, 90% of which is the restaurant's (450 / 500).
        $this->assertEquals(90.00, (float) $refund->vendor_deduction);
        $this->assertSame($order->restaurant_id, (int) $refund->restaurant_id);
    }

    public function test_the_delivery_fee_part_of_a_refund_stays_with_the_platform(): void
    {
        $order = $this->deliveredOrder();

        // The full order total: only the 500 food subtotal counts towards the restaurant.
        $refund = $this->refundFor($order, 540);

        $this->assertEquals(450.00, (float) $refund->vendor_deduction);
    }

    public function test_a_platform_refund_never_touches_the_restaurant(): void
    {
        $refund = $this->refundFor($this->deliveredOrder(), 100, 'platform');

        $this->assertEquals(0.00, (float) $refund->vendor_deduction);
    }

    public function test_only_delivered_and_paid_orders_can_be_refunded(): void
    {
        $restaurant = $this->makeRestaurant();
        $customer   = $this->makeUser('customer');

        foreach ([['preparing', 'pending'], ['delivered', 'pending'], ['cancelled', 'pending']] as [$status, $payment]) {
            $order = $this->makeOrder($restaurant, $customer, $status, ['payment_status' => $payment]);

            try {
                $this->refundFor($order, 50);
                $this->fail("Order in {$status}/{$payment} was refunded");
            } catch (DomainException $e) {
                $this->assertStringContainsString('delivered', $e->getMessage());
            }
        }

        $this->assertSame(0, Refund::count());
    }

    public function test_refunds_together_can_never_pass_the_order_total(): void
    {
        $order = $this->deliveredOrder();

        $this->refundFor($order, 400);

        try {
            $this->refundFor($order, 200);
            $this->fail('Refund above the remaining amount was accepted');
        } catch (DomainException $e) {
            $this->assertStringContainsString('140.00', $e->getMessage());
        }

        $this->assertSame(140.0, $this->service()->remaining($order->fresh()));
        $this->refundFor($order, 140); // exactly what is left
        $this->assertSame(0.0, $this->service()->remaining($order->fresh()));
    }

    public function test_a_cancelled_refund_gives_the_amount_back(): void
    {
        $order  = $this->deliveredOrder();
        $refund = $this->refundFor($order, 540);

        $this->assertTrue($this->service()->cancel($refund->id, $this->makeUser('admin')));

        $this->assertSame(540.0, $this->service()->remaining($order->fresh()));
        $this->refundFor($order, 100);
    }

    public function test_zero_negative_and_unknown_inputs_are_refused(): void
    {
        $order = $this->deliveredOrder();
        $admin = $this->makeUser('admin');

        $bad = [
            [$order->order_number, 0.0, 'food_quality', 'vendor'],
            [$order->order_number, -5.0, 'food_quality', 'vendor'],
            [$order->order_number, 10.0, 'banana', 'vendor'],
            [$order->order_number, 10.0, 'food_quality', 'rider'],
            ['NOPE0000', 10.0, 'food_quality', 'vendor'],
        ];

        foreach ($bad as [$number, $amount, $reason, $bearer]) {
            try {
                $this->service()->create($admin, $number, $amount, $reason, $bearer);
                $this->fail('Bad input was accepted: ' . json_encode([$number, $amount, $reason, $bearer]));
            } catch (DomainException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }

        $this->assertSame(0, Refund::count());
    }

    public function test_the_order_number_is_matched_without_hash_and_case(): void
    {
        $order = $this->deliveredOrder();

        $refund = $this->service()->create(
            $this->makeUser('admin'),
            '#' . strtolower($order->order_number),
            50,
            'other',
            'platform'
        );

        $this->assertSame($order->id, (int) $refund->order_id);
    }

    public function test_an_order_without_a_restaurant_cannot_be_charged_to_a_vendor(): void
    {
        $customer = $this->makeUser('customer');

        $order = Order::forceCreate([
            'order_number'    => 'KKADMINREF1',
            'order_type'      => 'admin',
            'restaurant_id'   => null,
            'customer_id'     => $customer->id,
            'status'          => 'delivered',
            'subtotal'        => 300,
            'delivery_fee'    => 49,
            'discount_amount' => 0,
            'total_amount'    => 349,
            'payment_method'  => 'cash_on_delivery',
            'payment_status'  => 'paid',
        ]);

        $this->expectException(DomainException::class);
        $this->service()->create($this->makeUser('admin'), $order->order_number, 50, 'other', 'vendor');
    }

    public function test_a_vendor_refund_without_a_money_record_has_no_deduction(): void
    {
        $order = $this->deliveredOrder(null, null, false);

        $this->assertEquals(0.00, (float) $this->refundFor($order, 100)->vendor_deduction);
    }

    public function test_a_ticket_must_belong_to_the_orders_customer(): void
    {
        $customer = $this->makeUser('customer');
        $order    = $this->deliveredOrder(null, $customer);
        $admin    = $this->makeUser('admin');

        $mine = SupportTicket::forceCreate([
            'ticket_number' => 'TKREF0001', 'customer_id' => $customer->id, 'category' => 'refund',
            'subject' => 'Cold food', 'message' => 'The food was cold when it arrived.', 'status' => 'open',
        ]);
        $other = SupportTicket::forceCreate([
            'ticket_number' => 'TKREF0002', 'customer_id' => $this->makeUser('customer')->id, 'category' => 'refund',
            'subject' => 'Other', 'message' => 'Somebody else entirely here.', 'status' => 'open',
        ]);

        $refund = $this->service()->create($admin, $order->order_number, 50, 'food_quality', 'platform', null, 'TKREF0001');
        $this->assertSame($mine->id, (int) $refund->support_ticket_id);

        $this->expectException(DomainException::class);
        $this->service()->create($admin, $order->order_number, 50, 'food_quality', 'platform', null, $other->ticket_number);
    }

    // ── paying / cancelling ──────────────────────────────────

    public function test_marking_paid_records_the_payment_and_tells_the_customer(): void
    {
        $customer = $this->makeUser('customer');
        $admin    = $this->makeUser('admin');
        $order    = $this->deliveredOrder(null, $customer);
        $refund   = $this->refundFor($order, 100);

        $this->assertTrue($this->service()->markPaid($refund->id, $admin, 'bkash', 'TXN123'));

        $fresh = $refund->fresh();
        $this->assertSame('paid', $fresh->status);
        $this->assertSame('bkash', $fresh->method);
        $this->assertSame('TXN123', $fresh->reference);
        $this->assertSame($admin->id, (int) $fresh->processed_by);
        $this->assertNotNull($fresh->paid_at);

        $this->assertSame(1, Notification::where('user_id', $customer->id)->where('type', 'refund_paid')->count());

        // A partial refund leaves the order itself as "paid".
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_refund_of_the_whole_total_marks_the_order_refunded(): void
    {
        $order = $this->deliveredOrder();

        $this->service()->markPaid($this->refundFor($order, 300)->id, $this->makeUser('admin'), 'cash', null);
        $this->assertSame('paid', $order->fresh()->payment_status);

        $this->service()->markPaid($this->refundFor($order, 240)->id, $this->makeUser('admin'), 'cash', null);
        $this->assertSame('refunded', $order->fresh()->payment_status);
    }

    public function test_a_refund_can_only_be_paid_or_cancelled_once(): void
    {
        $admin  = $this->makeUser('admin');
        $refund = $this->refundFor($this->deliveredOrder(), 100);

        $this->assertTrue($this->service()->markPaid($refund->id, $admin, 'nagad', null));
        $this->assertFalse($this->service()->markPaid($refund->id, $admin, 'nagad', null));
        $this->assertFalse($this->service()->cancel($refund->id, $admin));
        $this->assertSame('paid', $refund->fresh()->status);
        $this->assertSame(1, Notification::where('type', 'refund_paid')->count());
    }

    public function test_an_unknown_payment_method_is_refused(): void
    {
        $refund = $this->refundFor($this->deliveredOrder(), 100);

        $this->assertFalse($this->service()->markPaid($refund->id, $this->makeUser('admin'), 'paypal', null));
        $this->assertSame('pending', $refund->fresh()->status);
    }

    public function test_the_payment_does_not_change_the_vendors_money_record(): void
    {
        $order  = $this->deliveredOrder();
        $refund = $this->refundFor($order, 540);

        $this->service()->markPaid($refund->id, $this->makeUser('admin'), 'bkash', null);

        $tx = PlatformTransaction::where('order_id', $order->id)->sole();
        $this->assertSame('success', $tx->status);
        $this->assertSame('pending', $tx->settlement_status);
    }

    // ── vendor payout ────────────────────────────────────────

    private function payoutService(): VendorPayoutService
    {
        return app(VendorPayoutService::class);
    }

    public function test_a_paid_vendor_refund_is_taken_off_the_next_payout(): void
    {
        $restaurant = $this->makeRestaurant();
        $this->deliveredOrder($restaurant);                    // 450 to pay out
        $refunded = $this->deliveredOrder($restaurant);        // + 450 to pay out
        $refund   = $this->refundFor($refunded, 100);          // restaurant carries 90
        $this->service()->markPaid($refund->id, $this->makeUser('admin'), 'bkash', null);

        $result = $this->payoutService()->generate(today(), today(), $this->makeUser('admin'));

        $payout = Payout::where('restaurant_id', $restaurant->id)->sole();
        $this->assertEquals(810.0, (float) $payout->net_amount);          // 900 - 90
        $this->assertEquals(-90.0, (float) $payout->adjustment_amount);
        $this->assertEquals(810.0, $result['total_net']);
        $this->assertSame($payout->id, (int) $refund->fresh()->payout_id);
    }

    public function test_a_refund_is_deducted_only_once(): void
    {
        $restaurant = $this->makeRestaurant();
        $order      = $this->deliveredOrder($restaurant);
        $refund     = $this->refundFor($order, 100);
        $this->service()->markPaid($refund->id, $this->makeUser('admin'), 'bkash', null);

        $admin = $this->makeUser('admin');
        $this->payoutService()->generate(today(), today(), $admin);

        // A new delivered order, a second run: the first refund is already used.
        $this->deliveredOrder($restaurant);
        $this->payoutService()->generate(today(), today(), $admin);

        $second = Payout::where('restaurant_id', $restaurant->id)->orderByDesc('id')->first();
        $this->assertEquals(450.0, (float) $second->net_amount);
        $this->assertEquals(0.0, (float) $second->adjustment_amount);
    }

    public function test_pending_cancelled_and_platform_refunds_are_not_deducted(): void
    {
        $restaurant = $this->makeRestaurant();
        $order      = $this->deliveredOrder($restaurant);

        $this->refundFor($order, 50);                                  // vendor, still pending
        $cancelled = $this->refundFor($order, 50);                     // vendor, cancelled
        $this->service()->cancel($cancelled->id, $this->makeUser('admin'));
        $platform = $this->refundFor($order, 50, 'platform');          // platform, paid
        $this->service()->markPaid($platform->id, $this->makeUser('admin'), 'cash', null);

        $this->payoutService()->generate(today(), today(), $this->makeUser('admin'));

        $payout = Payout::where('restaurant_id', $restaurant->id)->sole();
        $this->assertEquals(450.0, (float) $payout->net_amount);
        $this->assertEquals(0.0, (float) $payout->adjustment_amount);
    }

    public function test_the_preview_shows_the_same_net_as_the_payout(): void
    {
        $restaurant = $this->makeRestaurant();
        $order      = $this->deliveredOrder($restaurant);
        $refund     = $this->refundFor($order, 100);
        $this->service()->markPaid($refund->id, $this->makeUser('admin'), 'bkash', null);

        $preview = $this->payoutService()->preview(today(), today());

        $this->assertEquals(360.0, $preview->sole()['net']);           // 450 - 90
        $this->assertNull($refund->fresh()->payout_id);                // preview changes nothing
    }

    public function test_a_failed_payout_gives_its_refunds_back(): void
    {
        $restaurant = $this->makeRestaurant();
        $order      = $this->deliveredOrder($restaurant);
        $refund     = $this->refundFor($order, 100);
        $admin      = $this->makeUser('admin');
        $this->service()->markPaid($refund->id, $admin, 'bkash', null);

        $this->payoutService()->generate(today(), today(), $admin);
        $payout = Payout::where('restaurant_id', $restaurant->id)->sole();

        $this->assertTrue($this->payoutService()->markFailed($payout->id, $admin, 'Wrong account'));
        $this->assertNull($refund->fresh()->payout_id);

        // The next payout takes the refund again.
        $this->payoutService()->generate(today(), today(), $admin);
        $again = Payout::where('restaurant_id', $restaurant->id)->orderByDesc('id')->first();
        $this->assertEquals(360.0, (float) $again->net_amount);
    }

    public function test_refunds_that_do_not_fit_into_the_payout_wait_for_the_next_one(): void
    {
        $restaurant = $this->makeRestaurant();
        $admin      = $this->makeUser('admin');

        // Old orders, already settled in an earlier payout: only their refunds are new.
        $oldA = $this->deliveredOrder($restaurant);
        $oldB = $this->deliveredOrder($restaurant);
        PlatformTransaction::whereIn('order_id', [$oldA->id, $oldB->id])->update(['settlement_status' => 'paid']);

        $first  = $this->refundFor($oldA, 540);     // restaurant carries 450
        $second = $this->refundFor($oldB, 540);     // and another 450
        $this->service()->markPaid($first->id, $admin, 'bkash', null);
        $this->service()->markPaid($second->id, $admin, 'bkash', null);

        // The only new sale is worth 450.
        $this->deliveredOrder($restaurant);

        $this->payoutService()->generate(today(), today(), $admin);

        $payout = Payout::where('restaurant_id', $restaurant->id)->sole();
        $this->assertEquals(0.0, (float) $payout->net_amount);                 // 450 - 450, never negative
        $this->assertSame($payout->id, (int) $first->fresh()->payout_id);      // oldest first
        $this->assertNull($second->fresh()->payout_id);                        // waits
    }
}
