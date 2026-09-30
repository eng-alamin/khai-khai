<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Admin er 5 ta homepage product — kono vendor/restaurant er sathe link nai,
     * 'section' field diye homepage e kothay dekhabe seta thik hoy.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Combo Meal Deal',       'price' => 349, 'compare_price' => 420, 'section' => 'special_offer', 'emoji' => '🎁'],
            ['name' => 'Weekend Family Pack',   'price' => 899, 'compare_price' => 1050, 'section' => 'special_offer', 'emoji' => '👨‍👩‍👧'],
            ['name' => 'Trending Beef Burger',  'price' => 199, 'compare_price' => null, 'section' => 'trending', 'emoji' => '🍔'],
            ['name' => 'Trending Cheese Pizza', 'price' => 450, 'compare_price' => null, 'section' => 'trending', 'emoji' => '🍕'],
            ['name' => 'Best Seller Biryani',   'price' => 260, 'compare_price' => null, 'section' => 'best_seller', 'emoji' => '⭐'],
        ];

        foreach ($items as $sortOrder => $item) {
            Product::updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'category_id'   => null,
                    'name'          => $item['name'],
                    'description'   => $item['name'] . ' — admin curated pick.',
                    'price'         => $item['price'],
                    'compare_price' => $item['compare_price'],
                    'emoji'         => $item['emoji'],
                    'image_url'     => null,
                    'section'       => $item['section'],
                    'sort_order'    => $sortOrder,
                    'is_active'     => true,
                ]
            );
        }
    }
}
