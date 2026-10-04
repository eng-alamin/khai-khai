<?php

namespace Tests\Feature;

use App\Models\Payout;
use App\Models\PlatformTransaction;
use App\Models\Restaurant;
use App\Services\CommissionService;
use App\Services\VendorPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplaceData;
use Tests\TestCase;

/**
 * Vendor settlement: one order is never paid out twice.
 */
class VendorPayoutServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMarketplaceData;

    /** A delivered order with its money record (subtotal 500, commission 50, vendor 450). */
    private function settledOrder(Restaurant $restaurant): PlatformTransaction
    {
        $order = $this->makeOrder($restaurant, $this->makeUser("customer"), "delivered");

        return PlatformTransaction::forceCreate([
            "order_id"            => $order->id,
            "vendor_amount"       => 450,
            "rider_amount"        => 40,
            "platform_commission" => 50,
            "commission_rate"     => 10,
            "gateway"             => "cod",
            "status"              => "success",
            "settlement_status"   => "pending",
        ]);
    }

    public function test_generate_creates_one_payout_per_restaurant_with_the_right_totals(): void
    {
        $a = $this->makeRestaurant();
        $b = $this->makeRestaurant();
        $this->settledOrder($a);
        $this->settledOrder($a);
        $this->settledOrder($b);

        $result = app(VendorPayoutService::class)->generate(today(), today(), $this->makeUser("admin"));

        $this->assertSame(2, $result["created"]);
        $this->assertEquals(1350.0, $result["total_net"]);

        $payoutA = Payout::where("restaurant_id", $a->id)->sole();
        $this->assertSame(2, (int) $payoutA->orders_count);
        $this->assertEquals(900.0, (float) $payoutA->net_amount);
        $this->assertEquals(100.0, (float) $payoutA->commission_amount);
        $this->assertSame("pending", $payoutA->status);

        $this->assertSame(0, PlatformTransaction::whereNull("payout_id")->count());
    }

    public function test_running_generate_twice_never_pays_the_same_order_again(): void
    {
        $this->settledOrder($this->makeRestaurant());
        $admin   = $this->makeUser("admin");
        $service = app(VendorPayoutService::class);

        $this->assertSame(1, $service->generate(today(), today(), $admin)["created"]);
        $this->assertSame(0, $service->generate(today(), today(), $admin)["created"]);
        $this->assertSame(1, Payout::count());
    }

    public function test_generate_can_be_limited_to_one_restaurant(): void
    {
        $a = $this->makeRestaurant();
        $b = $this->makeRestaurant();
        $this->settledOrder($a);
        $this->settledOrder($b);

        app(VendorPayoutService::class)->generate(today(), today(), $this->makeUser("admin"), $a->id);

        $this->assertSame(1, Payout::count());
        $this->assertSame($a->id, (int) Payout::first()->restaurant_id);
    }

    public function test_orders_outside_the_date_range_are_left_alone(): void
    {
        $txn = $this->settledOrder($this->makeRestaurant());
        $txn->forceFill(["created_at" => now()->subDays(10)])->save();

        $result = app(VendorPayoutService::class)->generate(today(), today(), $this->makeUser("admin"));

        $this->assertSame(0, $result["created"]);
    }

    public function test_mark_paid_settles_the_transactions_and_cannot_repeat(): void
    {
        $this->settledOrder($this->makeRestaurant());
        $admin   = $this->makeUser("admin");
        $service = app(VendorPayoutService::class);
        $service->generate(today(), today(), $admin);
        $payout = Payout::first();

        $this->assertTrue($service->markPaid($payout->id, $admin, "bkash", "TRX9"));

        $this->assertSame("paid", $payout->fresh()->status);
        $this->assertSame("paid", PlatformTransaction::first()->settlement_status);

        $this->assertFalse($service->markPaid($payout->id, $admin, "bkash", "TRX9"));
        $this->assertFalse($service->markFailed($payout->id, $admin, "too late"));
    }

    public function test_mark_failed_releases_the_orders_for_the_next_payout(): void
    {
        $this->settledOrder($this->makeRestaurant());
        $admin   = $this->makeUser("admin");
        $service = app(VendorPayoutService::class);
        $service->generate(today(), today(), $admin);
        $payout = Payout::first();

        $this->assertTrue($service->markFailed($payout->id, $admin, "wrong account"));
        $this->assertNull(PlatformTransaction::first()->payout_id);

        $this->assertSame(1, $service->generate(today(), today(), $admin)["created"]);
        $this->assertSame(2, Payout::count());
    }

    public function test_commission_uses_the_restaurant_rate_and_splits_the_order(): void
    {
        $service = app(CommissionService::class);
        $order   = $this->makeOrder($this->makeRestaurant(["commission_rate" => 8]), $this->makeUser("customer"), "delivered");

        // The restaurant has its own rate.
        $this->assertEquals(8.0, $service->rateFor($order));

        $split = $service->split($order);
        $this->assertEquals(40.0, $split["platform_commission"]);
        $this->assertEquals(460.0, $split["vendor_amount"]);
        $this->assertEquals(40.0, $split["rider_amount"]);
    }
}
