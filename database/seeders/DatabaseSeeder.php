<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. php artisan db:seed
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Admin User',    'role' => 'admin',    'phone' => '01700000001', 'email' => 'admin@demo.com'],
            ['name' => 'Customer User1', 'role' => 'customer', 'phone' => '01700000011', 'email' => 'customer1@demo.com'],
            ['name' => 'Customer User2', 'role' => 'customer', 'phone' => '01700000012', 'email' => 'customer2@demo.com'],
            ['name' => 'Customer User3', 'role' => 'customer', 'phone' => '01700000013', 'email' => 'customer3@demo.com'],
            ['name' => 'Customer User4', 'role' => 'customer', 'phone' => '01700000014', 'email' => 'customer4@demo.com'],
            ['name' => 'Customer User5', 'role' => 'customer', 'phone' => '01700000015', 'email' => 'customer5@demo.com'],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'uuid'              => (string) Str::uuid(),
                    'name'              => $data['name'],
                    'phone'             => $data['phone'],
                    'password'          => 'password',
                    'role'              => $data['role'],
                    'email_verified_at' => now(),
                    'is_verified'       => true,
                    'is_active'         => true,
                ]
            );
        }
    }
}
