<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a user (vendor owner or rider) wants to receive money.
 * One account per method per user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('method', ['bkash', 'nagad', 'bank']);
            $table->string('account_number', 30);
            $table->string('account_name', 100)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_accounts');
    }
};
