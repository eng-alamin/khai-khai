<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FCM token store kore actual push notification pathanor jonno.
     * push_notifications table ta broadcast er log rakhe, eta actual
     * device target korar jonno lage.
     */
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('token', 255)->unique();

            $table->enum('platform', ['android', 'ios', 'web'])->default('android');

            $table->string('device_id', 100)->nullable()->comment('Device unique identifier, optional');

            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
