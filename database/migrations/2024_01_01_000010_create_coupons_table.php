<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('description', 255);
            $table->enum('type', ['percentage', 'fixed_amount', 'free_delivery']);
            $table->decimal('value', 10, 2);
            $table->unsignedInteger('min_order_amount')->nullable()->comment('BDT paisa');
            $table->unsignedInteger('max_discount')->nullable()->comment('BDT paisa cap for % type');
            $table->unsignedInteger('usage_limit')->nullable()->comment('null = unlimited');
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('per_user_limit')->nullable();
            $table->timestamp('valid_from');
            $table->timestamp('valid_until');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('is_active');
            $table->index(['valid_from', 'valid_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
