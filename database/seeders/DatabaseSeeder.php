<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan db:seed
     *
     * - Every environment: reference data only (settings, categories).
     * - production: also creates the first admin from SEED_ADMIN_* env values.
     * - local / testing: demo users, restaurants, foods, sliders, products.
     */
    public function run(): void
    {
        $this->call([
            AdminSettingSeeder::class,
            CategorySeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call([
                UserSeeder::class,
                SliderSeeder::class,
                RestaurantSeeder::class,
                RiderProfileSeeder::class,
                FoodSeeder::class,
                ProductSeeder::class,
            ]);

            return;
        }

        $this->call(AdminUserSeeder::class);
    }
}