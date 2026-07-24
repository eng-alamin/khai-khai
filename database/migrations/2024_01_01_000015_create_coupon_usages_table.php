<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {

            $table->id();

            $table->foreignId('coupon_id')
                ->constrained('coupons');

            $table->foreignId('user_id')
                ->constrained('users');

            $table->foreignId('order_id')
                ->unique()
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->enum('discount_type', [
                'fixed',
                'percentage'
            ])->nullable();

            $table->decimal('discount_value',15,2)
                ->nullable();

            $table->decimal('discount_applied',15,2)
                ->comment('Actual discount applied');

            $table->timestamp('used_at')
                ->useCurrent();

            $table->timestamps();

            $table->index([
                'coupon_id',
                'user_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
