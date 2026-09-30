<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Restaurant;
use Illuminate\Support\Str;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Ek jon vendor = ek ta restaurant (owner_id link).
     */
    public function run(): void
    {
        $names = [
            'Bhaat Ghor',
            'Kacchi Bhai',
            'Chittagong Grill',
            'Sylhet Sylheti Kitchen',
            'Dhaka Deshi Deli',
        ];

        $vendors = User::where('role', 'vendor')->orderBy('id')->get();

        foreach ($vendors as $index => $vendor) {
            $name = $names[$index] ?? ('Vendor Restaurant ' . ($index + 1));

            Restaurant::updateOrCreate(
                ['owner_id' => $vendor->id],
                [
                    'name'             => $name,
                    'slug'             => Str::slug($name) . '-' . $vendor->id,
                    'category'         => 'Bangladeshi',
                    'emoji'            => '🍛',
                    'address'          => 'House ' . (10 + $index) . ', Road ' . (5 + $index) . ', Dhaka',
                    'city'             => 'Dhaka',
                    'phone'            => $vendor->phone,
                    'avg_rating'       => null,
                    'total_reviews'    => 0,
                    'tag'              => null,
                    'commission_rate'  => 15.00,
                    'is_open'          => true,
                    'is_approved'      => true,
                    'is_active'        => true,
                ]
            );
        }
    }
}
