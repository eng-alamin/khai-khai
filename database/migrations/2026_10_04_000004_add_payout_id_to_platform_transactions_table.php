<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links every platform transaction to the vendor payout that settles it.
 *
 * A transaction can belong to at most ONE payout, so the same order can
 * never be paid out twice. NULL = not yet included in any payout.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_transactions', function (Blueprint $table) {
            $table->foreignId('payout_id')
                ->nullable()
                ->after('order_id')
                ->constrained('payouts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('platform_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payout_id');
        });
    }
};
