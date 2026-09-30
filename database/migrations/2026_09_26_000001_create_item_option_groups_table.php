<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ekta food/product er variant (size) ba addon (extra topping) group.
     * Zeমন: "Size" (single select, required) ba "Extra Toppings" (multiple select, optional).
     */
    public function up(): void
    {
        Schema::create('item_option_groups', function (Blueprint $table) {
            $table->id();

            // Polymorphic: App\Models\Food ba App\Models\Product
            $table->morphs('optionable');

            $table->string('name', 80)->comment('e.g. Size, Extra Toppings, Spice Level');

            $table->enum('selection_type', ['single', 'multiple'])
                ->default('single')
                ->comment('single = radio (size/variant), multiple = checkbox (addons)');

            $table->boolean('is_required')->default(false);

            // "multiple" type hole customer max koyta value select korte parbe (null = unlimited)
            $table->unsignedTinyInteger('max_select')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['optionable_type', 'optionable_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_option_groups');
    }
};
