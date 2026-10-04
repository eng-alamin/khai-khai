<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-restaurant menu sections (e.g. "Chef's Special", "Combo Deals").
 *
 * categories stays the global food taxonomy (Burger, Pizza, ...).
 * A section is optional: foods without one keep showing under their
 * category on the customer page, so existing menus look the same.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_sections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('restaurant_id')
                ->constrained('restaurants')
                ->cascadeOnDelete();

            $table->string('name', 80);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['restaurant_id', 'name']);
            $table->index(['restaurant_id', 'is_active', 'sort_order']);
        });

        Schema::table('foods', function (Blueprint $table) {
            $table->foreignId('menu_section_id')
                ->nullable()
                ->after('category_id')
                ->constrained('menu_sections')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('menu_section_id');
        });

        Schema::dropIfExists('menu_sections');
    }
};
