<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 130)->unique();
            $table->string('category', 60);
            $table->string('emoji', 10)->nullable();
            $table->string('logo_url', 255)->nullable();
            $table->string('banner_url', 255)->nullable();
            $table->text('address');
            $table->string('city', 60);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone', 15)->nullable();
            $table->unsignedInteger('avg_delivery_min')->nullable()->comment('minutes');
            $table->unsignedInteger('avg_delivery_max')->nullable()->comment('minutes');
            $table->unsignedInteger('delivery_fee')->default(4900)->comment('BDT paisa');
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->unsignedInteger('total_reviews')->default(0);
            $table->string('tag', 40)->nullable()->comment('e.g. সেরা, জনপ্রিয়');
            $table->decimal('commission_rate', 5, 2)->default(15.00)->comment('%');
            $table->boolean('is_open')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('owner_id');
            $table->index('city');
            $table->index('is_open');
            $table->index('is_approved');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
