<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A rider who cannot hand over an order that is already "picked_up"
     * (customer not answering, wrong address, ...) reports a delivery issue.
     * The order stays "picked_up" until an admin decides: retry or mark failed.
     * No new status is added on purpose, so no other screen needs to change.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_issue_reason', 255)->nullable()->after('cancel_reason');
            $table->timestamp('delivery_issue_at')->nullable()->after('delivery_issue_reason');

            // Admin looks for flagged orders.
            $table->index('delivery_issue_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['delivery_issue_at']);
            $table->dropColumn(['delivery_issue_reason', 'delivery_issue_at']);
        });
    }
};
