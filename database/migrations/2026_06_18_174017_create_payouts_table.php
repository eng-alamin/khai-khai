<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('payout_number', 12)->unique()->comment('e.g. PO260605');
            $table->foreignId('restaurant_id')->constrained('restaurants');
 
            // ── Settlement period this payout covers ──
            $table->date('period_start');
            $table->date('period_end');
 
            // ── Aggregated amounts (BDT paisa) ──
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('gross_subtotal')->comment('BDT paisa, sum of order subtotals');
            $table->decimal('commission_rate', 5, 2)->comment('% snapshot at payout time');
            $table->unsignedInteger('commission_amount')->comment('BDT paisa');
            $table->unsignedInteger('adjustment_amount')->default(0)->comment('BDT paisa, +/- corrections, refunds, etc.');
            $table->unsignedInteger('net_amount')->comment('BDT paisa, amount actually paid to restaurant');
 
            // ── Payout status lifecycle ──
            $table->enum('status', [
                'pending',
                'processing',
                'paid',
                'failed',
            ])->default('pending');
 
            // ── Payment rail details ──
            $table->enum('payment_method', ['bkash', 'nagad', 'bank_transfer', 'cash'])->nullable();
            $table->string('transaction_reference', 100)->nullable()->comment('Gateway/bank transaction id');
            $table->text('failure_reason')->nullable();
 
            $table->timestamp('scheduled_at')->nullable()->comment('When this payout is due to be sent');
            $table->timestamp('paid_at')->nullable();
 
            $table->timestamps();
 
            $table->index('restaurant_id');
            $table->index('status');
            $table->index('period_start');
            $table->index('period_end');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
