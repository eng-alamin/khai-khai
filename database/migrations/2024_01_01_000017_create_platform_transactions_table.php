<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_transactions', function (Blueprint $table) {

        $table->id();

        $table->foreignId('order_id')
            ->constrained('orders')
            ->cascadeOnDelete();

        $table->decimal('vendor_amount',15,2);

        $table->decimal('rider_amount',15,2);

        $table->decimal('platform_commission',15,2);

        $table->decimal('commission_rate',5,2);

        $table->enum('gateway', [
            'bkash',
            'nagad',
            'card',
            'cod',
        ]);

        $table->string('gateway_txn_id',80)
            ->nullable()
            ->unique();

        $table->decimal('gateway_fee',15,2)
            ->default(0);

        $table->enum('status', [
            'pending',
            'processing',
            'success',
            'failed',
            'refunded',
            'partial_refund',
        ])->default('pending');

        $table->enum('settlement_status', [
            'pending',
            'paid',
            'failed',
        ])->default('pending');

        $table->timestamp('settled_at')
            ->nullable();

        $table->timestamps();

        $table->index([
            'order_id',
            'status'
        ]);
    });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_transactions');
    }
};
