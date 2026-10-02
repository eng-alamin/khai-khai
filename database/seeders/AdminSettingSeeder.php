<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminSettingSeeder extends Seeder
{
    /**
     * Safe to run many times: existing keys are left untouched,
     * so values an admin already changed are never overwritten.
     * php artisan db:seed --class=AdminSettingSeeder
     */
    public function run(): void
    {
        $now = now();

        $defaults = [
            ['minimum_delivery_charge', '40', 'Base delivery fee in BDT (Taka), covers the included distance'],
            ['per_km_delivery_rate', '10', 'BDT (Taka) charged per additional km beyond included_km_in_minimum'],
            ['included_km_in_minimum', '1', 'Distance in km covered by the minimum delivery charge'],
            ['default_commission_rate', '12', 'Default platform commission (%) when a restaurant has no own rate'],
            ['premium_vendor_rate', '8', 'Commission (%) for premium vendors'],
            ['payment_cycle', 'monthly', 'How often vendors and riders are paid out'],
            ['order_auto_cancel_minutes', '15', 'Minutes a vendor order may stay pending before it is auto-cancelled'],
            ['rider_pickup_timeout_minutes', '20', 'Minutes a rider may hold an accepted order without picking it up before it is released'],
            ['platform_name', 'KhaiKhai Food Delivery', null],
            ['support_email', 'support@khaikhai.com.bd', null],
            ['helpline', '09612-KHAI (5424)', null],
        ];

        foreach ($defaults as [$key, $value, $description]) {
            DB::table('admin_settings')->insertOrIgnore([
                'key'         => $key,
                'value'       => $value,
                'description' => $description,
                'updated_by'  => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }
}