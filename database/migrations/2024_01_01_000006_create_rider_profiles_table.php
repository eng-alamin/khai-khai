<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('vehicle_type', 40);
            $table->string('vehicle_plate', 20)->unique()->nullable();
            $table->string('license_number', 30)->unique()->nullable();
            $table->string('nid_number', 20)->unique()->nullable();
            $table->string('zone', 80)->nullable()->comment('e.g. Dhaka-Metro, Gazipur');
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->unsignedInteger('total_deliveries')->default(0);
            $table->boolean('is_online')->default(false);
            $table->decimal('current_lat', 10, 7)->nullable();
            $table->decimal('current_lng', 10, 7)->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->index('is_online');
            $table->index('is_approved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_profiles');
    }
};
