<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * type=food -> 5 ta, type=product -> 5 ta
     */
    public function run(): void
    {
        $categories = [
            // Food categories — restaurant/foods er jonno
            ['type' => 'food', 'name' => 'Biryani & Rice'],
            ['type' => 'food', 'name' => 'Fast Food'],
            ['type' => 'food', 'name' => 'Desserts'],
            ['type' => 'food', 'name' => 'Beverages'],
            ['type' => 'food', 'name' => 'Curry & Bhaji'],

            // Product categories — admin homepage products er jonno
            ['type' => 'product', 'name' => 'Combo Deals'],
            ['type' => 'product', 'name' => 'Snacks'],
            ['type' => 'product', 'name' => 'Bakery'],
            ['type' => 'product', 'name' => 'Frozen Items'],
            ['type' => 'product', 'name' => 'Grocery'],
        ];

        foreach ($categories as $sortOrder => $category) {
            Category::updateOrCreate(
                ['slug' => Str::slug($category['type'] . '-' . $category['name'])],
                [
                    'type'       => $category['type'],
                    'name'       => $category['name'],
                    'image_url'  => null,
                    'sort_order' => $sortOrder,
                    'is_active'  => true,
                ]
            );
        }
    }
}
