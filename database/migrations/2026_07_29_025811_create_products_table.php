<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
 
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            // Basic info (mirrors menu_items structure but standalone — no FK)
            $table->string('name', 120);
            $table->string('slug', 130)->unique();
 
            $table->text('description')->nullable();
 
            // Pricing
            $table->decimal('price', 12, 2)
                ->default(0)
                ->comment('Selling price in BDT');
 
            $table->decimal('compare_price', 12, 2)
                ->nullable()
                ->comment('Original price before discount');
 
            // Display
            $table->string('emoji', 10)->nullable();
            $table->string('image_url', 255)->nullable();
 
            // Homepage section (fixed values enforced in app layer, e.g. special_offer, trending, best_seller)
            $table->string('section', 40);
 
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
 
            $table->timestamps();
 
            $table->index(['section', 'is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
