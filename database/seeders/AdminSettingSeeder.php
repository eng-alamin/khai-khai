<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminSettingSeeder extends Seeder
{
    /**
     * Run the database seeds. 
     * php artisan db:seed --class=AdminSettingSeeder
     */
    public function run(): void
    {
        $now = now();

        DB::table('admin_settings')->insert([
            [
                'key' => 'minimum_delivery_charge',
                'value' => '40', // BDT (Taka), covers included_km_in_minimum
                'description' => 'Base delivery fee in BDT (Taka), covers the included distance',
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'per_km_delivery_rate',
                'value' => '10', // BDT (Taka) per extra km
                'description' => 'BDT (Taka) charged per additional km beyond included_km_in_minimum',
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'included_km_in_minimum',
                'value' => '1',
                'description' => 'Distance in km covered by the minimum delivery charge',
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'default_commission_rate',
                'value' => '12',
                'description' => null,
                'updated_by' => 2,
                'created_at' => '2026-07-10 12:59:24',
                'updated_at' => '2026-07-10 12:59:24',
            ],
            [
                'key' => 'premium_vendor_rate',
                'value' => '8',
                'description' => null,
                'updated_by' => 2,
                'created_at' => '2026-07-10 12:59:24',
                'updated_at' => '2026-07-10 12:59:24',
            ],
            [
                'key' => 'payment_cycle',
                'value' => 'monthly',
                'description' => null,
                'updated_by' => 2,
                'created_at' => '2026-07-10 12:59:24',
                'updated_at' => '2026-07-10 12:59:24',
            ],
            [
                'key' => 'platform_name',
                'value' => 'KhaiKhai Food Delivery',
                'description' => null,
                'updated_by' => 2,
                'created_at' => '2026-07-10 12:59:38',
                'updated_at' => '2026-07-10 12:59:38',
            ],
            [
                'key' => 'support_email',
                'value' => 'support@khaikhai.com.bd',
                'description' => null,
                'updated_by' => 2,
                'created_at' => '2026-07-10 12:59:38',
                'updated_at' => '2026-07-10 12:59:38',
            ],
            [
                'key' => 'helpline',
                'value' => '09612-KHAI (5424)',
                'description' => null,
                'updated_by' => 2,
                'created_at' => '2026-07-10 12:59:38',
                'updated_at' => '2026-07-10 12:59:38',
            ],
        ]);
    }
}
