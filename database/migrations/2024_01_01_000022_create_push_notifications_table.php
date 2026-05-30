<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->text('body');
            $table->enum('target_role', ['customer', 'vendor', 'rider', 'all'])->nullable()
                ->comment('null = specific user only');
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('null = broadcast to role');
            $table->enum('channel', ['push', 'sms', 'in_app'])->default('in_app');
            $table->foreignId('sent_by')->constrained('users');
            $table->unsignedInteger('sent_count')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index('target_role');
            $table->index('target_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notifications');
    }
};
