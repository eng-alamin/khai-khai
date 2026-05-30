<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 12)->unique()->comment('e.g. KK2601');
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('restaurant_id')->constrained('restaurants');
            $table->foreignId('rider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delivery_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->json('delivery_address_snapshot')->comment('Immutable address copy at order time');
            $table->enum('status', [
                'pending',
                'confirmed',
                'preparing',
                'picked_up',
                'delivered',
                'cancelled',
            ])->default('pending');
            $table->unsignedInteger('subtotal')->comment('BDT paisa');
            $table->unsignedInteger('delivery_fee')->default(4900)->comment('BDT paisa');
            $table->unsignedInteger('discount_amount')->default(0)->comment('BDT paisa');
            $table->unsignedInteger('total_amount')->comment('BDT paisa');
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->enum('payment_method', ['bkash', 'nagad', 'card', 'cash_on_delivery']);
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'failed'])->default('pending');
            $table->text('special_instructions')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('restaurant_id');
            $table->index('rider_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
