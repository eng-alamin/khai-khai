<?php

namespace Tests\Feature;

use App\Models\AdminSetting;
use App\Models\RiderCashDeposit;
use App\Models\RiderEarning;
use App\Models\RiderPayout;
use App\Models\User;
use App\Services\RiderBalanceService;
use App\Services\RiderCashService;
use App\Services\RiderPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplaceData;
use Tests\TestCase;

/**
 * Rider balance, cash deposits and the rider payout steps.
 */
class RiderFinanceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMarketplaceData;

    /** An earning that holds no cash (the order is not delivered yet). */
    private function giveEarning(User $rider, float $amount): void
    {
        $order = $this->makeOrder($this->makeRestaurant(), $this->makeUser("customer"), "picked_up", ["rider" => $rider]);

        RiderEarning::forceCreate([
            "rider_id"        => $rider->id,
            "order_id"        => $order->id,
            "amount"          => $amount,
        ]);
    }

    /** A delivered cash-on-delivery order: the rider holds `total` in cash. */
    private function giveCashOrder(User $rider, float $total = 540): void
    {
        $this->makeOrder($this->makeRestaurant(), $this->makeUser("customer"), "delivered", [
            "rider"        => $rider,
            "total_amount" => $total,
        ]);
    }

    private function makePayout(User $rider, float $amount, string $status = "pending"): RiderPayout
    {
        return RiderPayout::forceCreate([
            "rider_id"       => $rider->id,
            "amount"         => $amount,
            "method"         => "bkash",
            "account_number" => "01700000000",
            "status"         => $status,
        ]);
    }

    public function test_cash_in_hand_is_collected_minus_deposited(): void
    {
        $rider = $this->makeRider();
        $this->giveCashOrder($rider, 540);
        $this->giveCashOrder($rider, 300);

        $cash = app(RiderCashService::class);
        $this->assertEquals(840.0, $cash->cashInHand($rider->id));

        $cash->recordDeposit($rider, 500, "cash", null, null, $this->makeUser("admin"));
        $this->assertEquals(340.0, $cash->cashInHand($rider->id));
    }

    public function test_a_deposit_cannot_be_more_than_the_cash_the_rider_holds(): void
    {
        $rider = $this->makeRider();
        $admin = $this->makeUser("admin");
        $this->giveCashOrder($rider, 540);

        $cash = app(RiderCashService::class);

        $this->assertNull($cash->recordDeposit($rider, 541, "cash", null, null, $admin));
        $this->assertSame(0, RiderCashDeposit::count());

        $this->assertNotNull($cash->recordDeposit($rider, 540, "cash", null, null, $admin));
        // Nothing left to hand over.
        $this->assertNull($cash->recordDeposit($rider, 1, "cash", null, null, $admin));
        $this->assertSame(1, RiderCashDeposit::count());
    }

    public function test_zero_or_negative_deposits_are_refused(): void
    {
        $rider = $this->makeRider();
        $admin = $this->makeUser("admin");
        $this->giveCashOrder($rider, 540);

        $cash = app(RiderCashService::class);

        $this->assertNull($cash->recordDeposit($rider, 0, "cash", null, null, $admin));
        $this->assertNull($cash->recordDeposit($rider, -50, "cash", null, null, $admin));
        $this->assertSame(0, RiderCashDeposit::count());
    }

    public function test_cash_held_is_taken_off_the_available_balance(): void
    {
        $rider = $this->makeRider();
        $this->giveEarning($rider, 100);
        $this->giveCashOrder($rider, 60);

        $balance = app(RiderBalanceService::class);

        // 100 earned - 60 cash still held.
        $this->assertEquals(40.0, $balance->availableFor($rider->id));

        // Handing the cash over opens the balance again.
        app(RiderCashService::class)->recordDeposit($rider, 60, "cash", null, null, $this->makeUser("admin"));
        $this->assertEquals(100.0, $balance->availableFor($rider->id));
    }

    public function test_available_balance_never_goes_below_zero(): void
    {
        $rider = $this->makeRider();
        $this->giveEarning($rider, 40);
        $this->giveCashOrder($rider, 540);

        $this->assertEquals(0.0, app(RiderBalanceService::class)->availableFor($rider->id));
    }

    public function test_cash_netting_can_be_switched_off(): void
    {
        $rider = $this->makeRider();
        $this->giveEarning($rider, 100);
        $this->giveCashOrder($rider, 60);

        AdminSetting::set("rider_cash_netting", "0");

        $this->assertEquals(100.0, app(RiderBalanceService::class)->availableFor($rider->id));
    }

    public function test_open_and_paid_requests_reserve_money_but_rejected_and_failed_do_not(): void
    {
        $rider = $this->makeRider();
        $this->giveEarning($rider, 1000);

        $this->makePayout($rider, 100, "pending");
        $this->makePayout($rider, 200, "approved");
        $this->makePayout($rider, 50, "paid");
        $this->makePayout($rider, 300, "rejected");
        $this->makePayout($rider, 400, "failed");

        $balance = app(RiderBalanceService::class);

        $this->assertEquals(350.0, $balance->reservedTotal($rider->id));
        $this->assertEquals(650.0, $balance->availableFor($rider->id));
    }

    public function test_has_open_request_only_for_pending_approved_processing(): void
    {
        $rider   = $this->makeRider();
        $balance = app(RiderBalanceService::class);

        $this->assertFalse($balance->hasOpenRequest($rider->id));

        $this->makePayout($rider, 100, "paid");
        $this->makePayout($rider, 100, "rejected");
        $this->assertFalse($balance->hasOpenRequest($rider->id));

        $this->makePayout($rider, 100, "approved");
        $this->assertTrue($balance->hasOpenRequest($rider->id));
    }

    public function test_rejecting_a_request_gives_the_money_back(): void
    {
        $rider  = $this->makeRider();
        $admin  = $this->makeUser("admin");
        $this->giveEarning($rider, 500);
        $payout = $this->makePayout($rider, 500, "pending");

        $balance = app(RiderBalanceService::class);
        $this->assertEquals(0.0, $balance->availableFor($rider->id));

        $this->assertTrue(app(RiderPayoutService::class)->reject($payout->id, $admin, "wrong number"));

        $this->assertEquals(500.0, $balance->availableFor($rider->id));
    }

    public function test_payout_steps_follow_the_allowed_order(): void
    {
        $rider   = $this->makeRider();
        $admin   = $this->makeUser("admin");
        $service = app(RiderPayoutService::class);
        $payout  = $this->makePayout($rider, 500, "pending");

        // Cannot be paid before it is approved.
        $this->assertFalse($service->markPaid($payout->id, $admin, "TRX1"));
        $this->assertSame("pending", $payout->fresh()->status);

        $this->assertTrue($service->approve($payout->id, $admin));
        // A second approve (double click / second admin) does nothing.
        $this->assertFalse($service->approve($payout->id, $admin));

        $this->assertTrue($service->markPaid($payout->id, $admin, "TRX1", "sent"));

        $fresh = $payout->fresh();
        $this->assertSame("paid", $fresh->status);
        $this->assertSame("TRX1", $fresh->transaction_id);
        $this->assertSame($admin->id, (int) $fresh->processed_by);
        $this->assertNotNull($fresh->resolved_at);
    }

    public function test_a_finished_request_cannot_be_changed_again(): void
    {
        $rider   = $this->makeRider();
        $admin   = $this->makeUser("admin");
        $service = app(RiderPayoutService::class);
        $payout  = $this->makePayout($rider, 500, "paid");

        $this->assertFalse($service->reject($payout->id, $admin, "x"));
        $this->assertFalse($service->markFailed($payout->id, $admin, "x"));
        $this->assertFalse($service->markPaid($payout->id, $admin, "TRX2"));
        $this->assertSame("paid", $payout->fresh()->status);
    }
}
