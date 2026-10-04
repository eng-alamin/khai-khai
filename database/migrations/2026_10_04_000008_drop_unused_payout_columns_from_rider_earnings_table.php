<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * rider_earnings is an "earned" ledger only. Payout state lives in
     * rider_payouts (see RiderBalanceService), so these four columns were
     * never written or read by the application:
     *   payout_status, payment_method, transaction_id, paid_at
     * Keeping two places for payout state is confusing, so they are removed.
     */
    public function up(): void
    {
        // Safety guard: never drop columns that hold real data.
        $hasData = DB::table('rider_earnings')
            ->where(function ($query) {
                $query->where('payout_status', '!=', 'pending')
                    ->orWhereNotNull('payment_method')
                    ->orWhereNotNull('transaction_id')
                    ->orWhereNotNull('paid_at');
            })
            ->exists();

        if ($hasData) {
            throw new RuntimeException(
                'rider_earnings has rows with payout data (payout_status / payment_method / '
                . 'transaction_id / paid_at). Review or export them first, then re-run this migration.'
            );
        }

        // The composite index must go before its column can be dropped.
        // (rider_id, created_at) still backs the rider_id foreign key.
        Schema::table('rider_earnings', function (Blueprint $table) {
            $table->dropIndex(['rider_id', 'payout_status']);
        });

        Schema::table('rider_earnings', function (Blueprint $table) {
            $table->dropColumn([
                'payout_status',
                'payment_method',
                'transaction_id',
                'paid_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('rider_earnings', function (Blueprint $table) {
            $table->enum('payout_status', [
                'pending',
                'processing',
                'paid',
                'failed',
            ])->default('pending')->after('distance_km');

            $table->enum('payment_method', [
                'bkash',
                'nagad',
                'bank_transfer',
            ])->nullable()->after('payout_status');

            $table->string('transaction_id', 100)->nullable()->after('payment_method');
            $table->timestamp('paid_at')->nullable()->after('transaction_id');

            $table->index(['rider_id', 'payout_status']);
        });
    }
};
