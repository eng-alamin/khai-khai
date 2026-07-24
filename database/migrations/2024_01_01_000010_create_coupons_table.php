<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();


            // Coupon identity
            $table->string('code', 50)
                ->unique();

            $table->string('description', 255)
                ->nullable();


            // Discount type
            $table->enum('type', [
                'percentage',
                'fixed_amount',
                'free_delivery',
            ]);


            // Discount value
            // percentage = 10.00 (%)
            // fixed_amount = 100.00 BDT
            $table->decimal('value', 10, 2)
                ->default(0);


            // Minimum order requirement
            $table->decimal('min_order_amount', 12, 2)
                ->default(0)
                ->comment('Minimum order amount required');


            // Maximum discount limit for percentage coupon
            $table->decimal('max_discount', 12, 2)
                ->nullable()
                ->comment('Maximum discount cap');


            // Usage control
            $table->unsignedInteger('usage_limit')
                ->nullable()
                ->comment('Null means unlimited usage');


            $table->unsignedInteger('used_count')
                ->default(0);


            $table->unsignedInteger('per_user_limit')
                ->nullable();


            // Validity
            $table->timestamp('valid_from')
                ->nullable();


            $table->timestamp('valid_until')
                ->nullable();


            $table->boolean('is_active')
                ->default(true);


            // Creator
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();


            $table->timestamps();


            // Indexes
            $table->index('is_active');

            $table->index([
                'valid_from',
                'valid_until'
            ]);

            $table->index([
                'type',
                'is_active'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
