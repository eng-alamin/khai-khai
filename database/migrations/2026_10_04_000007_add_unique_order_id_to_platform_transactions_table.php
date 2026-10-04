<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One order must have exactly one platform transaction.
     *
     * Order::transaction() is a hasOne() and RiderDeliveryService uses
     * firstOrCreate(['order_id' => ...]), so the application already treats
     * the relation as 1:1. This migration makes the database enforce the
     * same rule, so a second row (double commission / double payout) can
     * never be inserted by any code path.
     */
    public function up(): void
    {
        // Refuse to run if duplicates already exist - they must be reviewed
        // by a human first (a blind delete could remove real money records).
        $duplicates = DB::table('platform_transactions')
            ->select('order_id', DB::raw('COUNT(*) as total'))
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('total', 'order_id');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Duplicate platform_transactions found for order_id(s): '
                . $duplicates->keys()->implode(', ')
                . '. Review and merge/remove the extra rows first, then re-run this migration.'
            );
        }

        Schema::table('platform_transactions', function (Blueprint $table) {
            $table->unique('order_id', 'platform_transactions_order_id_unique');
        });
    }

    public function down(): void
    {
        // The composite index (order_id, status) still backs the foreign key,
        // so dropping the unique index here is safe.
        Schema::table('platform_transactions', function (Blueprint $table) {
            $table->dropUnique('platform_transactions_order_id_unique');
        });
    }
};
