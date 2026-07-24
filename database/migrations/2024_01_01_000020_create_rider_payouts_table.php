<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_payouts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('rider_id')
                ->constrained('users');

            $table->decimal('amount',15,2)
                ->comment('Requested payout amount');

            $table->enum('method', [
                'bkash',
                'bank',
            ]);

            $table->string('account_number',30);

            $table->json('payment_account_snapshot')
                ->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'processing',
                'paid',
                'rejected',
                'failed',
            ])->default('pending');

            $table->string('transaction_id',100)
                ->nullable();

            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('admin_note')
                ->nullable();

            $table->timestamp('resolved_at')
                ->nullable();

            $table->timestamps();


            $table->index([
                'rider_id',
                'status',
                'created_at'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_payouts');
    }
};
