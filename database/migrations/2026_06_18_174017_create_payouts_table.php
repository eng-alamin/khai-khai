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

            $table->string('payout_number', 20)
                ->unique()
                ->comment('Unique payout reference number');


            $table->foreignId('restaurant_id')
                ->constrained('restaurants');


            // Settlement period
            $table->date('period_start');

            $table->date('period_end');


            // Settlement summary
            $table->unsignedInteger('orders_count')
                ->default(0);


            $table->decimal('gross_subtotal', 12, 2)
                ->default(0)
                ->comment('Total order subtotal amount');


            $table->decimal('commission_rate', 5, 2)
                ->default(0)
                ->comment('Commission percentage snapshot');


            $table->decimal('commission_amount', 12, 2)
                ->default(0)
                ->comment('Platform commission amount');


            $table->decimal('adjustment_amount', 12, 2)
                ->default(0)
                ->comment('Manual adjustment, refund or correction');


            $table->decimal('net_amount', 12, 2)
                ->default(0)
                ->comment('Final payable amount to restaurant');


            $table->char('currency', 3)
                ->default('BDT');


            // Payout lifecycle
            $table->enum('status', [
                'pending',
                'processing',
                'paid',
                'failed',
            ])
            ->default('pending');


            // Payment details
            $table->enum('payment_method', [
                'bkash',
                'nagad',
                'bank_transfer',
                'cash',
            ])
            ->nullable();


            $table->string('transaction_reference', 100)
                ->nullable()
                ->comment('Bank or gateway transaction id');


            $table->text('failure_reason')
                ->nullable();


            $table->timestamp('scheduled_at')
                ->nullable()
                ->comment('Scheduled payout processing time');


            $table->timestamp('paid_at')
                ->nullable();


            // Admin who processed payout
            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();


            $table->timestamps();


            // Indexes
            $table->index([
                'restaurant_id',
                'status'
            ]);

            $table->index([
                'period_start',
                'period_end'
            ]);

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
