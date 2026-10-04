<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refunds of delivered orders (customers pay cash on delivery, so a refund is
 * money the platform sends back by bKash / Nagad / bank / cash).
 *
 *  pending   -> admin decided it, money not sent yet
 *  paid      -> money sent to the customer
 *  cancelled -> decided against it before sending
 *
 * bearer = who carries the cost. For "vendor" the restaurant's share
 * (vendor_deduction) is taken off its next payout (payout_id = that payout).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_number', 24)->unique();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->nullOnDelete();
            $table->foreignId('support_ticket_id')->nullable()->constrained('support_tickets')->nullOnDelete();

            $table->decimal('amount', 12, 2);
            $table->enum('reason', ['food_quality', 'missing_item', 'wrong_item', 'late_delivery', 'other']);
            $table->text('note')->nullable();

            $table->enum('bearer', ['vendor', 'platform']);
            $table->decimal('vendor_deduction', 12, 2)->default(0);
            $table->foreignId('payout_id')->nullable()->constrained('payouts')->nullOnDelete();

            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->string('method', 20)->nullable();
            $table->string('reference', 100)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['restaurant_id', 'status', 'payout_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
