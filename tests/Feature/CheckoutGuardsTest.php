<?php

namespace Tests\Feature;

use App\Livewire\Customer\CartComponent;
use App\Models\AdminSetting;
use App\Models\Food;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsMarketplaceData;
use Tests\TestCase;

/**
 * The server-side rules a customer cannot skip at checkout:
 * minimum order, maximum delivery distance, deactivated account.
 *
 * The cart lives in the session under "kk_cart", so each test puts the cart
 * there first and then presses "place order".
 */
class CheckoutGuardsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMarketplaceData;

    private function cartFor(Food $food, int $qty): array
    {
        return [
            $food->id => [
                'kind'          => 'food',
                'name'          => $food->name,
                'price'         => (float) $food->price,
                'qty'           => $qty,
                'image_url'     => null,
                'emoji'         => null,
                'restaurant_id' => $food->restaurant_id,
            ],
        ];
    }

    /** @return array{0: User, 1: Restaurant, 2: Food} */
    private function scene(float $addressLat = 23.7810, float $addressLng = 90.4110): array
    {
        $restaurant = $this->makeRestaurant();               // 23.78, 90.41
        $food       = $this->makeFood($restaurant, 200);
        $customer   = $this->makeUser('customer');
        $this->makeAddress($customer, $addressLat, $addressLng);

        return [$customer, $restaurant, $food];
    }

    private function place(User $customer, array $cart): \Livewire\Features\SupportTesting\Testable
    {
        session(['kk_cart' => $cart]);

        return Livewire::actingAs($customer)
            ->test(CartComponent::class)
            ->call('placeOrder');
    }

    // ── Minimum order ────────────────────────────────────────

    public function test_an_order_below_the_restaurant_minimum_is_refused(): void
    {
        [$customer, $restaurant, $food] = $this->scene();
        $this->setMinOrder($restaurant, 500);

        $this->place($customer, $this->cartFor($food, 2)) // 2 x 200 = 400 < 500
            ->assertDispatched('notify');

        $this->assertSame(0, Order::count());
    }

    public function test_an_order_that_reaches_the_minimum_is_placed(): void
    {
        [$customer, $restaurant, $food] = $this->scene();
        $this->setMinOrder($restaurant, 500);

        $this->place($customer, $this->cartFor($food, 3)) // 600 >= 500
            ->assertDispatched('order-placed');

        $order = Order::sole();
        $this->assertEquals(600.00, (float) $order->subtotal);
        $this->assertSame('pending', $order->status);
    }

    public function test_no_minimum_set_means_any_amount_is_accepted(): void
    {
        [$customer, , $food] = $this->scene();

        $this->place($customer, $this->cartFor($food, 1))
            ->assertDispatched('order-placed');

        $this->assertSame(1, Order::count());
    }

    // ── Maximum delivery distance ────────────────────────────

    public function test_an_address_beyond_the_distance_limit_is_refused(): void
    {
        // 0.2 degrees of latitude is about 22 km from the restaurant.
        [$customer, , $food] = $this->scene(addressLat: 23.9800, addressLng: 90.4100);
        AdminSetting::set('max_delivery_distance_km', '10');

        $this->place($customer, $this->cartFor($food, 1))
            ->assertDispatched('notify');

        $this->assertSame(0, Order::count());
    }

    public function test_an_address_inside_the_distance_limit_is_accepted(): void
    {
        [$customer, , $food] = $this->scene(); // about 0.15 km away
        AdminSetting::set('max_delivery_distance_km', '10');

        $this->place($customer, $this->cartFor($food, 1))
            ->assertDispatched('order-placed');

        $this->assertSame(1, Order::count());
    }

    public function test_a_limit_of_zero_means_no_distance_limit(): void
    {
        [$customer, , $food] = $this->scene(addressLat: 23.9800, addressLng: 90.4100);
        AdminSetting::set('max_delivery_distance_km', '0');

        $this->place($customer, $this->cartFor($food, 1))
            ->assertDispatched('order-placed');

        $this->assertSame(1, Order::count());
    }

    // ── Deactivated account ──────────────────────────────────

    public function test_a_deactivated_customer_cannot_place_an_order(): void
    {
        [$customer, , $food] = $this->scene();
        $customer->forceFill(['is_active' => false])->save();

        $this->place($customer->fresh(), $this->cartFor($food, 1));

        $this->assertSame(0, Order::count());
        $this->assertGuest();
    }

    public function test_a_deactivated_customer_is_logged_out_of_customer_pages(): void
    {
        $customer = $this->makeUser('customer', ['is_active' => false]);

        $this->actingAs($customer)
            ->get(route('customer.orders'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
