<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. php artisan db:seed
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AdminSettingSeeder::class,
        ]);
    }
}
