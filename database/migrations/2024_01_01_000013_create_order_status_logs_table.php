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
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('note')->nullable()->comment('Internal note');
            $table->text('reason')->nullable()->comment('Reason for rejection/cancellation/change');
            $table->enum('changed_type', [
                'system',
                'customer',
                'restaurant',
                'rider',
                'admin',
            ])->default('system');
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_logs');
    }
};
