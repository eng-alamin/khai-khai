<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('restaurant_id')->constrained('restaurants');
            $table->foreignId('rider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('food_rating')->comment('1–5 stars');
            $table->unsignedTinyInteger('delivery_rating')->nullable()->comment('1–5 stars');
            $table->text('comment')->nullable();
            $table->boolean('is_published')->default(true)->comment('Admin moderation flag');
            $table->timestamps();

            $table->index('customer_id');
            $table->index('restaurant_id');
            $table->index('rider_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
