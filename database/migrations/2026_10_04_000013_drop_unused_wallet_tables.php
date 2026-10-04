<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The wallet was never built into any screen, cart or checkout: nothing reads
 * or writes it except the unused helpers on the User model. The two tables and
 * their models are removed.
 *
 * If a wallet is wanted later, build the whole feature (customer screen,
 * checkout payment, refunds) and create the tables again - down() restores them.
 *
 * Safe-guard: if either table already holds rows the migration stops BEFORE
 * changing anything, so no money record is lost silently.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['wallet_transactions', 'wallets'] as $tableName) {
            if (Schema::hasTable($tableName) && DB::table($tableName)->exists()) {
                throw new RuntimeException(
                    "Table [{$tableName}] contains data. Export/review it first, then re-run this migration."
                );
            }
        }

        // Transactions first: they have a foreign key to the wallets.
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }

    public function down(): void
    {
        if (! Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->decimal('balance', 15, 2)->default(0)->comment('Current wallet balance in BDT');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
                $table->enum('type', ['credit', 'debit']);
                $table->decimal('amount', 15, 2);
                $table->decimal('balance_after', 15, 2);
                $table->enum('source', [
                    'order_refund', 'order_payment', 'admin_adjustment',
                    'topup', 'cashback', 'referral_bonus', 'withdrawal',
                ]);
                $table->nullableMorphs('reference');
                $table->text('description')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['wallet_id', 'created_at']);
                $table->index('source');
            });
        }
    }
};
