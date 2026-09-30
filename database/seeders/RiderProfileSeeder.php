<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RiderProfile;

class RiderProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicleTypes = ['Motorcycle', 'Bicycle', 'Motorcycle', 'Motorcycle', 'Bicycle'];
        $zones        = ['Dhaka-Metro', 'Gazipur', 'Dhaka-Metro', 'Narayanganj', 'Dhaka-Metro'];

        $riders = User::where('role', 'rider')->orderBy('id')->get();

        foreach ($riders as $index => $rider) {
            RiderProfile::updateOrCreate(
                ['user_id' => $rider->id],
                [
                    'vehicle_type'     => $vehicleTypes[$index] ?? 'Motorcycle',
                    'vehicle_plate'    => 'DHK-' . str_pad((string) (1000 + $index), 4, '0', STR_PAD_LEFT),
                    'license_number'   => 'LIC-' . str_pad((string) (2000 + $index), 5, '0', STR_PAD_LEFT),
                    'nid_number'       => 'NID-' . str_pad((string) (3000 + $index), 6, '0', STR_PAD_LEFT),
                    'zone'             => $zones[$index] ?? 'Dhaka-Metro',
                    'avg_rating'       => null,
                    'total_deliveries' => 0,
                    'is_online'        => false,
                    'is_approved'      => true,
                ]
            );
        }
    }
}
