<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Item options (size / add-on groups) were never built: no screen, cart or
 * checkout reads or writes them, and order_items has no column to store a
 * chosen option. The two tables and their models are removed.
 *
 * If options are wanted later, build the whole feature (vendor screen, cart,
 * order_items snapshot) and create the tables again - down() restores them.
 *
 * Safe-guard: if either table already holds rows the migration stops BEFORE
 * changing anything, so no data is lost silently.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['item_option_values', 'item_option_groups'] as $tableName) {
            if (Schema::hasTable($tableName) && DB::table($tableName)->exists()) {
                throw new RuntimeException(
                    "Table [{$tableName}] contains data. Export/review it first, then re-run this migration."
                );
            }
        }

        // Values first: they have a foreign key to the groups.
        Schema::dropIfExists('item_option_values');
        Schema::dropIfExists('item_option_groups');
    }

    public function down(): void
    {
        if (! Schema::hasTable('item_option_groups')) {
            Schema::create('item_option_groups', function (Blueprint $table) {
                $table->id();
                $table->morphs('optionable');
                $table->string('name', 80)->comment('e.g. Size, Extra Toppings, Spice Level');
                $table->enum('selection_type', ['single', 'multiple'])->default('single');
                $table->boolean('is_required')->default(false);
                $table->unsignedTinyInteger('max_select')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['optionable_type', 'optionable_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('item_option_values')) {
            Schema::create('item_option_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('item_option_group_id')
                    ->constrained('item_option_groups')
                    ->cascadeOnDelete();
                $table->string('name', 80);
                $table->decimal('extra_price', 10, 2)->default(0);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['item_option_group_id', 'is_active']);
            });
        }
    }
};
