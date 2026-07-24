<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();


            $table->foreignId('customer_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();


            $table->foreignId('default_address_id')
                ->nullable()
                ->constrained('customer_addresses')
                ->nullOnDelete();


            // Customer statistics
            $table->unsignedInteger('total_orders')
                ->default(0);


            $table->decimal('avg_rating_given', 3, 2)
                ->nullable()
                ->comment('Average rating customer gives to restaurants');


            $table->unsignedInteger('cancelled_orders')
                ->default(0);


            $table->decimal('total_spent', 12, 2)
                ->default(0)
                ->comment('Total completed order amount');


            $table->timestamp('last_order_at')
                ->nullable();


            $table->timestamps();


            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
