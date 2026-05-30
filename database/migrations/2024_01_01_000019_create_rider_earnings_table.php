<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained('users');
            $table->foreignId('order_id')->unique()->constrained('orders');
            $table->unsignedInteger('amount')->comment('Earning in BDT paisa');
            $table->decimal('distance_km', 6, 2)->nullable();
            $table->enum('payout_status', ['pending', 'paid'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('rider_id');
            $table->index(['rider_id', 'payout_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_earnings');
    }
};
