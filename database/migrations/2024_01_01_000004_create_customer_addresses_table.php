<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();


            $table->foreignId('customer_id')
                ->constrained('users')
                ->cascadeOnDelete();


            // Address label
            $table->enum('label', [
                'home',
                'office',
                'other',
            ])
            ->default('home');


            // Address details
            $table->text('full_address');


            $table->string('city', 60)
                ->default('Dhaka');


            $table->string('area', 100)
                ->nullable();


            $table->string('postal_code', 10)
                ->nullable();


            // Map location
            $table->decimal('latitude', 10, 7)
                ->nullable();


            $table->decimal('longitude', 10, 7)
                ->nullable();


            // Default address
            $table->boolean('is_default')
                ->default(false);


            $table->timestamps();


            // Indexes
            $table->index('customer_id');

            $table->index([
                'customer_id',
                'is_default'
            ]);

            $table->index([
                'latitude',
                'longitude'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
