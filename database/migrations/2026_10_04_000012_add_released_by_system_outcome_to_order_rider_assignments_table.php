<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * When the scheduled timeout takes an order back from a rider who never
 * picked it up, the history now says "released_by_system" instead of
 * "removed_by_staff" (no staff member was involved).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_rider_assignments', function (Blueprint $table) {
            $table->enum('outcome', [
                'active',
                'delivered',
                'released_by_rider',
                'removed_by_staff',
                'delivery_failed',
                'released_by_system',
            ])->default('active')->change();
        });
    }

    public function down(): void
    {
        // The new value cannot exist in the old enum: map it back first.
        DB::table('order_rider_assignments')
            ->where('outcome', 'released_by_system')
            ->update(['outcome' => 'removed_by_staff']);

        Schema::table('order_rider_assignments', function (Blueprint $table) {
            $table->enum('outcome', [
                'active',
                'delivered',
                'released_by_rider',
                'removed_by_staff',
                'delivery_failed',
            ])->default('active')->change();
        });
    }
};
