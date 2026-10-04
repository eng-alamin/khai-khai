<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email    = env('SEED_ADMIN_EMAIL');
        $password = env('SEED_ADMIN_PASSWORD');

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->command?->error('Set a valid SEED_ADMIN_EMAIL in .env. No admin was created.');
            return;
        }

        if (! $password || strlen($password) < 12) {
            $this->command?->error('SEED_ADMIN_PASSWORD must be at least 12 characters. No admin was created.');
            return;
        }

        if (User::where('email', $email)->exists()) {
            $this->command?->warn("User {$email} already exists. Nothing changed.");
            return;
        }

        User::create([
            'uuid'              => (string) Str::uuid(),
            'name'              => env('SEED_ADMIN_NAME', 'Administrator'),
            'email'             => $email,
            'password'          => $password, // hashed by the User model cast
            'role'              => 'admin',
            'email_verified_at' => now(),
            'is_verified'       => true,
            'is_active'         => true,
        ]);

        $this->command?->info("Admin {$email} created. Remove SEED_ADMIN_PASSWORD from .env now.");
    }
}
