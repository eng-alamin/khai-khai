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
            $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->cascadeOnDelete()
                ->comment('null = platform-wide promotion');
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->enum('type', ['item_discount', 'buy_x_get_y', 'flash_deal']);
            $table->decimal('discount_value', 10, 2);
            $table->enum('applies_to', ['all_items', 'category', 'specific_item']);
            $table->unsignedBigInteger('target_id')->nullable()->comment('category_id or menu_item_id');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('restaurant_id');
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
