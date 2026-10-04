<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\CustomerAddress;
use App\Models\Food;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RiderProfile;
use App\Models\User;
use App\Models\VendorSetting;
use App\Services\RiderAssignmentService;
use Illuminate\Support\Str;

/**
 * Small fixture helpers shared by the feature tests.
 *
 * Everything is created with forceCreate() on purpose: many columns (status,
 * totals, is_approved ...) are deliberately NOT mass-assignable in the real
 * code, but a test has to be able to set them directly.
 */
trait BuildsMarketplaceData
{
    private int $seq = 0;

    protected function next(): int
    {
        return ++$this->seq;
    }

    protected function makeUser(string $role = 'customer', array $overrides = []): User
    {
        $n = $this->next();

        return User::forceCreate(array_merge([
            'uuid'      => (string) Str::uuid(),
            'name'      => ucfirst($role) . " {$n}",
            'phone'     => '017000000' . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'email'     => "{$role}{$n}@example.test",
            'password'  => bcrypt('password'),
            'role'      => $role,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeRider(bool $approved = true, bool $online = true): User
    {
        $rider = $this->makeUser('rider');

        RiderProfile::forceCreate([
            'user_id'      => $rider->id,
            'vehicle_type' => 'bike',
            'is_approved'  => $approved,
            'is_online'    => $online,
        ]);

        return $rider->fresh();
    }

    protected function makeRestaurant(array $overrides = []): Restaurant
    {
        $n     = $this->next();
        $owner = $this->makeUser('vendor');

        return Restaurant::forceCreate(array_merge([
            'owner_id'        => $owner->id,
            'name'            => "Test Restaurant {$n}",
            'slug'            => "test-restaurant-{$n}",
            'category'        => 'Bangla Food',
            'address'         => 'Test road',
            'city'            => 'Dhaka',
            'latitude'        => 23.7800000,
            'longitude'       => 90.4100000,
            'commission_rate' => 10,
            'is_open'         => true,
            'is_approved'     => true,
            'is_active'       => true,
        ], $overrides));
    }

    protected function setMinOrder(Restaurant $restaurant, float $amount): void
    {
        VendorSetting::forceCreate([
            'restaurant_id'    => $restaurant->id,
            'min_order_amount' => $amount,
        ]);
    }

    protected function makeFood(Restaurant $restaurant, float $price = 200): Food
    {
        $n = $this->next();

        $category = Category::forceCreate([
            'type'      => 'food',
            'name'      => "Category {$n}",
            'slug'      => "category-{$n}",
            'is_active' => true,
        ]);

        return Food::forceCreate([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $category->id,
            'name'          => "Food {$n}",
            'price'         => $price,
            'is_available'  => true,
        ]);
    }

    protected function makeAddress(User $customer, float $lat, float $lng): CustomerAddress
    {
        return CustomerAddress::forceCreate([
            'customer_id'  => $customer->id,
            'label'        => 'home',
            'full_address' => 'House 1, Test road',
            'city'         => 'Dhaka',
            'area'         => 'Test area',
            'postal_code'  => '1200',
            'latitude'     => $lat,
            'longitude'    => $lng,
            'is_default'   => true,
        ]);
    }

    /**
     * An order in the given status. Pass 'rider' to give it to a rider (an
     * "active" assignment row is opened too, exactly like acceptDelivery()).
     */
    protected function makeOrder(Restaurant $restaurant, User $customer, string $status, array $overrides = []): Order
    {
        $n     = $this->next();
        $rider = $overrides['rider'] ?? null;
        unset($overrides['rider']);

        $order = Order::forceCreate(array_merge([
            'order_number'    => 'KKTEST' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'order_type'      => 'vendor',
            'restaurant_id'   => $restaurant->id,
            'customer_id'     => $customer->id,
            'status'          => $status,
            'subtotal'        => 500,
            'delivery_fee'    => 40,
            'discount_amount' => 0,
            'total_amount'    => 540,
            'payment_method'  => 'cash_on_delivery',
            'payment_status'  => 'pending',
        ], $overrides));

        if ($rider !== null) {
            $order->forceFill([
                'rider_id'    => $rider->id,
                'assigned_at' => now(),
                'accepted_at' => now(),
            ])->save();

            app(RiderAssignmentService::class)->open($order->id, $rider->id);
        }

        return $order->fresh();
    }
}
