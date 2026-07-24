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


            $table->foreignId('restaurant_id')
                ->unique()
                ->constrained('restaurants')
                ->cascadeOnDelete();


            // Order handling settings
            $table->boolean('auto_accept')
                ->default(false)
                ->comment('Automatically accept incoming orders');


            $table->unsignedSmallInteger('prep_time_min')
                ->default(20)
                ->comment('Default food preparation time in minutes');


            $table->boolean('notification_sound')
                ->default(true);


            // Financial settings
            $table->decimal('min_order_amount', 12, 2)
                ->default(0)
                ->comment('Minimum order amount required');


            $table->decimal('commission_rate', 5, 2)
                ->default(0)
                ->comment('Restaurant commission percentage snapshot');


            // Restaurant operational settings
            $table->boolean('is_accepting_orders')
                ->default(true)
                ->comment('Restaurant online/offline status');


            $table->boolean('auto_reject')
                ->default(false)
                ->comment('Auto reject orders after timeout');


            $table->unsignedSmallInteger('order_timeout_minutes')
                ->default(10)
                ->comment('Time before pending order expires');


            $table->timestamps();


            $table->index([
                'restaurant_id',
                'is_accepting_orders'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_settings');
    }
};
