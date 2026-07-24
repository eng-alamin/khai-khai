<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_payouts', function (Blueprint $table) {

        $table->id();

        $table->string('payout_number', 30)
            ->unique();

        $table->foreignId('restaurant_id')
            ->constrained('restaurants');

        $table->date('period_start');

        $table->date('period_end');

        $table->decimal('gross_sales',15,2);

        $table->decimal('platform_fee',15,2);

        $table->decimal('net_payout',15,2);

        $table->json('payment_account_snapshot')
            ->nullable();

        $table->enum('method', [
            'bkash',
            'bank_transfer',
        ]);

        $table->enum('status', [
            'pending',
            'processing',
            'paid',
            'failed',
            'on_hold',
        ])->default('pending');

        $table->timestamp('paid_at')
            ->nullable();

        $table->foreignId('processed_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->timestamps();


        $table->unique([
            'restaurant_id',
            'period_start',
            'period_end',
        ]);

        $table->index([
            'restaurant_id',
            'status',
        ]);
    });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payouts');
    }
};
