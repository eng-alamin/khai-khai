<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the "ready" status (food is prepared and waiting for a rider).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'confirmed',
                'preparing',
                'ready',
                'picked_up',
                'on_the_way',
                'delivered',
                'cancelled',
                'rejected',
            ])->default('pending')->change();
        });
    }

    /**
     * Orders already in "ready" go back to "preparing" so the enum change is safe.
     */
    public function down(): void
    {
        DB::table('orders')->where('status', 'ready')->update(['status' => 'preparing']);

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'confirmed',
                'preparing',
                'picked_up',
                'on_the_way',
                'delivered',
                'cancelled',
                'rejected',
            ])->default('pending')->change();
        });
    }
};
