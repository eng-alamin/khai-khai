<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * balance column ke sotti bishash na kore, ei table thekei hisheb hobe —
     * prottekta credit/debit ekhane log thakbe, ar wallets.balance ta
     * shudhu quick-read cache hishebe update thakbe.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();

            $table->enum('type', ['credit', 'debit']);

            $table->decimal('amount', 15, 2);

            $table->decimal('balance_after', 15, 2)->comment('Wallet balance transaction er por');

            $table->enum('source', [
                'order_refund',
                'order_payment',
                'admin_adjustment',
                'topup',
                'cashback',
                'referral_bonus',
                'withdrawal',
            ]);

            // Optional: kon order/coupon/onno kichur karone ei transaction hoyeche
            $table->nullableMorphs('reference');

            $table->text('description')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
