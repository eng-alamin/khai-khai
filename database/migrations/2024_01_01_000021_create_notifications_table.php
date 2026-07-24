<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('type',60);

            $table->string('title',120);

            $table->text('body');

            $table->json('data')
                ->nullable();

            $table->string('action_url')
                ->nullable();

            $table->enum('priority', [
                'low',
                'normal',
                'high',
            ])->default('normal');

            $table->timestamp('read_at')
                ->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index([
                'user_id',
                'read_at'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
