<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();


            $table->foreignId('restaurant_id')
                ->constrained('restaurants')
                ->cascadeOnDelete();


            $table->foreignId('category_id')
                ->nullable()
                ->constrained('menu_categories')
                ->nullOnDelete();


            // Item information
            $table->string('name', 120);

            $table->text('description')
                ->nullable();


            // Pricing
            $table->decimal('price', 12, 2)
                ->default(0)
                ->comment('Selling price in BDT');


            $table->decimal('compare_price', 12, 2)
                ->nullable()
                ->comment('Original price before discount');


            // Display
            $table->string('emoji', 10)
                ->nullable();


            $table->string('image_url', 255)
                ->nullable();


            $table->boolean('is_featured')
                ->default(false);


            // Availability
            $table->boolean('is_available')
                ->default(true);


            $table->unsignedSmallInteger('sort_order')
                ->default(0);


            $table->timestamps();


            // Indexes
            $table->index('restaurant_id');

            $table->index('category_id');

            $table->index([
                'restaurant_id',
                'is_available'
            ]);

            $table->index([
                'restaurant_id',
                'is_featured'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
