<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Weekly opening hours, one row per restaurant per weekday.
 *
 * day_of_week : 0 = Sunday ... 6 = Saturday (same as Carbon::dayOfWeek).
 * closes_at earlier than opens_at means the slot ends after midnight.
 *
 * vendor_settings gets two columns:
 *  - use_opening_hours    : vendor wants the schedule to open/close the restaurant.
 *  - scheduled_open_state : last state the schedule applied. The sync command only
 *                           changes restaurants.is_open when this changes, so a
 *                           manual open/close toggle by the vendor is respected
 *                           until the next opening/closing time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_hours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('restaurant_id')
                ->constrained('restaurants')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['restaurant_id', 'day_of_week']);
        });

        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->boolean('use_opening_hours')
                ->default(false)
                ->after('order_timeout_minutes');

            $table->boolean('scheduled_open_state')
                ->nullable()
                ->after('use_opening_hours');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->dropColumn(['use_opening_hours', 'scheduled_open_state']);
        });

        Schema::dropIfExists('restaurant_hours');
    }
};
