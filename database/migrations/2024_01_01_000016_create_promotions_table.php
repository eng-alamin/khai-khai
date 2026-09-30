<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {

        $table->id();

        $table->foreignId('restaurant_id')
            ->nullable()
            ->constrained('restaurants')
            ->nullOnDelete();

        $table->string('title',120);

        $table->text('description')
            ->nullable();

        $table->string('type',50);

        $table->enum('discount_type', [
            'fixed',
            'percentage',
        ])->default('fixed');

        $table->decimal('discount_value',10,2);

        $table->enum('applies_to', [
            'all_items',
            'category',
            'specific_food',
            'specific_product',
        ]);

        $table->unsignedBigInteger('target_id')
            ->nullable()
            ->comment('categories.id / foods.id / products.id depending on applies_to');

        $table->decimal('minimum_order_amount',15,2)
            ->default(0);

        $table->unsignedInteger('usage_limit')
            ->nullable();

        $table->unsignedInteger('used_count')
            ->default(0);

        $table->timestamp('starts_at')
            ->nullable();

        $table->timestamp('ends_at')
            ->nullable();

        $table->boolean('is_active')
            ->default(true);

        $table->timestamps();


        $table->index([
            'restaurant_id',
            'is_active'
        ]);

        $table->index([
            'is_active',
            'starts_at',
            'ends_at'
        ]);
    });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
