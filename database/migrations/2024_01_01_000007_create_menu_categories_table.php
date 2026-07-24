<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();


            $table->foreignId('restaurant_id')
                ->constrained('restaurants')
                ->cascadeOnDelete();


            // Category information
            $table->string('name', 80);

            $table->string('emoji', 10)
                ->nullable();


            // Ordering
            $table->unsignedSmallInteger('sort_order')
                ->default(0);


            // Status
            $table->boolean('is_active')
                ->default(true);


            $table->timestamps();


            // Prevent duplicate category inside same restaurant
            $table->unique([
                'restaurant_id',
                'name'
            ]);


            // Query optimization
            $table->index([
                'restaurant_id',
                'is_active',
                'sort_order'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_categories');
    }
};
