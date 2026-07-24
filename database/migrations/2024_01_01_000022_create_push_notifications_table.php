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

            $table->string('title',120);

            $table->text('body');

            $table->enum('target_role', [
                'customer',
                'restaurant',
                'rider',
                'all',
            ])->nullable();

            $table->foreignId('target_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->json('target_filter')
                ->nullable();

            $table->enum('channel', [
                'push',
                'sms',
                'in_app',
            ])->default('in_app');

            $table->json('data')
                ->nullable();

            $table->enum('status', [
                'draft',
                'queued',
                'sending',
                'sent',
                'failed',
            ])->default('draft');

            $table->foreignId('sent_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->unsignedInteger('sent_count')
                ->default(0);

            $table->unsignedInteger('failed_count')
                ->default(0);

            $table->timestamp('sent_at')
                ->nullable();

            $table->timestamps();


            $table->index([
                'target_role',
                'status'
            ]);

            $table->index('target_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notifications');
    }
};
