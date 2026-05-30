<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->unique()->constrained('restaurants')->cascadeOnDelete();
            $table->boolean('auto_accept')->default(false);
            $table->unsignedInteger('prep_time_min')->default(20)->comment('default prep time in minutes');
            $table->boolean('notification_sound')->default(true);
            $table->unsignedInteger('min_order_amount')->nullable()->comment('BDT paisa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_settings');
    }
};
