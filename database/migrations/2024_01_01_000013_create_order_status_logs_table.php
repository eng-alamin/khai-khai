<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('from_status', [
                'pending', 'confirmed', 'preparing', 'picked_up', 'delivered', 'cancelled',
            ])->nullable()->comment('null on first log');
            $table->enum('to_status', [
                'pending', 'confirmed', 'preparing', 'picked_up', 'delivered', 'cancelled',
            ]);
            $table->foreignId('changed_by')->constrained('users');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_logs');
    }
};
