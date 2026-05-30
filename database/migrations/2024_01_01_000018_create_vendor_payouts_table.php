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
            $table->foreignId('restaurant_id')->constrained('restaurants');
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('gross_sales')->comment('Total sales in period (BDT paisa)');
            $table->unsignedBigInteger('platform_fee')->comment('Commission deducted (BDT paisa)');
            $table->unsignedBigInteger('net_payout')->comment('Amount transferred to vendor (BDT paisa)');
            $table->enum('method', ['bkash', 'bank_transfer']);
            $table->enum('status', ['pending', 'paid', 'on_hold'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('restaurant_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payouts');
    }
};
