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
            
            $table->string('order_number', 20)->unique();

            // Kono restaurant/vendor er order kina seta bole. Admin er product
            // order hole restaurant_id null thakbe (admin nijei seller).
            $table->enum('order_type', ['vendor', 'admin'])->default('vendor');

            $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('rider_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->json('restaurant_snapshot')->nullable();
            $table->json('customer_snapshot')->nullable();
            $table->json('rider_snapshot')->nullable();
            $table->json('coupon_snapshot')->nullable();

            $table->foreignId('delivery_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->json('delivery_address_snapshot')->nullable()->comment('Immutable delivery address at order time');
            $table->decimal('delivery_distance_km', 6, 2)->nullable()->comment('Distance in KM at order time');

            $table->enum('status', [
                'pending',
                'confirmed',
                'preparing',
                'picked_up',
                'on_the_way',
                'delivered',
                'cancelled',
                'rejected',
            ])->default('pending');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('delivery_fee', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount',15,2)->default(0);
            $table->decimal('tip_amount',15,2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->enum('payment_method', [
                'bkash',
                'nagad',
                'card',
                'cash_on_delivery',
            ])->nullable();
            $table->enum('payment_status', [
                'pending',
                'processing',
                'paid',
                'failed',
                'refunded',
            ])->default('pending');
            $table->text('special_instructions')->nullable();
            $table->unsignedSmallInteger('estimated_delivery_minutes')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->enum('order_source',[
                'web',
                'android',
                'ios',
                'admin',
            ])->default('web');
            $table->timestamps();

            $table->softDeletes();

            $table->index(['restaurant_id', 'status', 'created_at']);
            $table->index(['order_type', 'status', 'created_at']);
            $table->index(['customer_id','created_at']);
            $table->index(['rider_id','status']);
            $table->index(['payment_status','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
