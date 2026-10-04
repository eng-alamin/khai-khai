<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per time a rider takes an order.
     *
     * orders.rider_id only keeps the CURRENT rider and is wiped when a rider
     * releases the order or staff removes the rider. This table keeps the
     * whole history: who took the order, when, and how it ended.
     */
    public function up(): void
    {
        Schema::create('order_rider_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('rider_id')->constrained('users');
            $table->timestamp('assigned_at');
            $table->timestamp('ended_at')->nullable();
            $table->enum('outcome', [
                'active',
                'delivered',
                'released_by_rider',
                'removed_by_staff',
                'delivery_failed',
            ])->default('active');
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'outcome']);
            $table->index(['rider_id', 'assigned_at']);
        });

        $this->backfillFromOrders();
    }

    public function down(): void
    {
        Schema::dropIfExists('order_rider_assignments');
    }

    /**
     * Create one row for every order that already has a rider, so "close the
     * active assignment" also works for orders taken before this migration.
     * (Earlier releases/removals were never stored in a table, so they cannot
     * be restored.)
     */
    private function backfillFromOrders(): void
    {
        $columns = [
            'order_id', 'rider_id', 'assigned_at', 'ended_at',
            'outcome', 'ended_by', 'created_at', 'updated_at',
        ];

        $now = now()->toDateTimeString();

        // Still in progress.
        DB::table('order_rider_assignments')->insertUsing(
            $columns,
            DB::table('orders')
                ->whereNotNull('rider_id')
                ->whereIn('status', ['ready', 'picked_up'])
                ->selectRaw(
                    "id, rider_id, COALESCE(assigned_at, accepted_at, created_at), NULL, 'active', NULL, ?, ?",
                    [$now, $now]
                )
        );

        // Delivered.
        DB::table('order_rider_assignments')->insertUsing(
            $columns,
            DB::table('orders')
                ->whereNotNull('rider_id')
                ->where('status', 'delivered')
                ->selectRaw(
                    "id, rider_id, COALESCE(assigned_at, accepted_at, created_at), delivered_at, 'delivered', NULL, ?, ?",
                    [$now, $now]
                )
        );

        // Admin marked the delivery as failed (the only way a rider order gets cancelled).
        DB::table('order_rider_assignments')->insertUsing(
            $columns,
            DB::table('orders')
                ->whereNotNull('rider_id')
                ->where('status', 'cancelled')
                ->selectRaw(
                    "id, rider_id, COALESCE(assigned_at, accepted_at, created_at), cancelled_at, 'delivery_failed', cancelled_by, ?, ?",
                    [$now, $now]
                )
        );
    }
};
