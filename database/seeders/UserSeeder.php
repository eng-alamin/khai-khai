<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Demo accounts share a known password: never allow them on a live server.
        if (app()->isProduction()) {
            throw new \RuntimeException('UserSeeder creates demo accounts and cannot run in production. Use AdminUserSeeder.');
        }

        $users = [
            // Admin — 1 ta
            ['name' => 'Admin User', 'role' => 'admin', 'phone' => '01700000001', 'email' => 'admin@demo.com'],

            // Customer — 5 ta
            ['name' => 'Customer User1', 'role' => 'customer', 'phone' => '01700000011', 'email' => 'customer1@demo.com'],
            ['name' => 'Customer User2', 'role' => 'customer', 'phone' => '01700000012', 'email' => 'customer2@demo.com'],
            ['name' => 'Customer User3', 'role' => 'customer', 'phone' => '01700000013', 'email' => 'customer3@demo.com'],
            ['name' => 'Customer User4', 'role' => 'customer', 'phone' => '01700000014', 'email' => 'customer4@demo.com'],
            ['name' => 'Customer User5', 'role' => 'customer', 'phone' => '01700000015', 'email' => 'customer5@demo.com'],

            // Vendor — 5 ta
            ['name' => 'Vendor User1', 'role' => 'vendor', 'phone' => '01700000021', 'email' => 'vendor1@demo.com'],
            ['name' => 'Vendor User2', 'role' => 'vendor', 'phone' => '01700000022', 'email' => 'vendor2@demo.com'],
            ['name' => 'Vendor User3', 'role' => 'vendor', 'phone' => '01700000023', 'email' => 'vendor3@demo.com'],
            ['name' => 'Vendor User4', 'role' => 'vendor', 'phone' => '01700000024', 'email' => 'vendor4@demo.com'],
            ['name' => 'Vendor User5', 'role' => 'vendor', 'phone' => '01700000025', 'email' => 'vendor5@demo.com'],

            // Rider — 5 ta
            ['name' => 'Rider User1', 'role' => 'rider', 'phone' => '01700000031', 'email' => 'rider1@demo.com'],
            ['name' => 'Rider User2', 'role' => 'rider', 'phone' => '01700000032', 'email' => 'rider2@demo.com'],
            ['name' => 'Rider User3', 'role' => 'rider', 'phone' => '01700000033', 'email' => 'rider3@demo.com'],
            ['name' => 'Rider User4', 'role' => 'rider', 'phone' => '01700000034', 'email' => 'rider4@demo.com'],
            ['name' => 'Rider User5', 'role' => 'rider', 'phone' => '01700000035', 'email' => 'rider5@demo.com'],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'uuid'              => (string) Str::uuid(),
                    'name'              => $data['name'],
                    'phone'             => $data['phone'],
                    'password'          => '12345678',
                    'role'              => $data['role'],
                    'email_verified_at' => now(),
                    'is_verified'       => true,
                    'is_active'         => true,
                ]
            );
        }
    }
}
