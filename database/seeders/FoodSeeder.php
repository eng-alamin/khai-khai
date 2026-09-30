<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Restaurant;
use App\Models\Food;

class FoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Prottek vendor/restaurant er jonno 5 ta food item.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Chicken Biryani', 'price' => 250, 'compare_price' => 300, 'emoji' => '🍛'],
            ['name' => 'Beef Tehari',     'price' => 220, 'compare_price' => null, 'emoji' => '🍚'],
            ['name' => 'Mutton Kacchi',   'price' => 380, 'compare_price' => 420, 'emoji' => '🍲'],
            ['name' => 'Chicken Fry',     'price' => 180, 'compare_price' => null, 'emoji' => '🍗'],
            ['name' => 'Vegetable Khichuri', 'price' => 150, 'compare_price' => null, 'emoji' => '🥘'],
        ];

        $restaurants = Restaurant::orderBy('id')->get();

        foreach ($restaurants as $restaurant) {
            foreach ($items as $sortOrder => $item) {
                Food::updateOrCreate(
                    [
                        'restaurant_id' => $restaurant->id,
                        'name'          => $item['name'],
                    ],
                    [
                        'category_id'    => null,
                        'description'    => $item['name'] . ' — ' . $restaurant->name . ' er special item.',
                        'price'          => $item['price'],
                        'compare_price'  => $item['compare_price'],
                        'image_url'      => null,
                        'is_featured'    => $sortOrder === 0,
                        'is_available'   => true,
                        'sort_order'     => $sortOrder,
                    ]
                );
            }
        }
    }
}
