<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ekta option group er vitorer actual value.
     * e.g. group "Size" -> values "Small" (+0), "Large" (+50)
     *      group "Extra Toppings" -> values "Extra Cheese" (+30), "Extra Egg" (+20)
     */
    public function up(): void
    {
        Schema::create('item_option_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_option_group_id')
                ->constrained('item_option_groups')
                ->cascadeOnDelete();

            $table->string('name', 80);

            $table->decimal('extra_price', 10, 2)
                ->default(0)
                ->comment('Base item price er sathe jog hobe, BDT');

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['item_option_group_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_option_values');
    }
};
