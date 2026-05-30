<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained('users');
            $table->unsignedInteger('amount')->comment('Requested payout amount (BDT paisa)');
            $table->enum('method', ['bkash', 'bank']);
            $table->string('account_number', 30)->comment('Destination wallet/bank number');
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('rider_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_payout_requests');
    }
};
