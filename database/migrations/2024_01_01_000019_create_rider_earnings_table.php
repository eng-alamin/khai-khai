<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_earnings', function (Blueprint $table) {

        $table->id();

        $table->foreignId('rider_id')
            ->constrained('users');

        $table->foreignId('order_id')
            ->unique()
            ->constrained('orders')
            ->cascadeOnDelete();

        $table->decimal('amount',15,2)
            ->comment('Total rider earning');

        $table->decimal('base_fare',15,2)
            ->default(0);

        $table->decimal('distance_bonus',15,2)
            ->default(0);

        $table->decimal('tip_amount',15,2)
            ->default(0);

        $table->decimal('distance_km',6,2)
            ->nullable();

        $table->enum('payout_status', [
            'pending',
            'processing',
            'paid',
            'failed',
        ])->default('pending');

        $table->enum('payment_method', [
            'bkash',
            'nagad',
            'bank_transfer',
        ])->nullable();

        $table->string('transaction_id',100)
            ->nullable();

        $table->timestamp('paid_at')
            ->nullable();

        $table->timestamps();


        $table->index([
            'rider_id',
            'payout_status'
        ]);

        $table->index([
            'rider_id',
            'created_at'
        ]);
    });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_earnings');
    }
};
