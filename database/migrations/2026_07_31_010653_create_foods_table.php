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
        Schema::create('foods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('restaurant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name', 120);
            $table->text('description')->nullable();

            $table->decimal('price', 12, 2)
                ->default(0)
                ->comment('Selling price in BDT');

            $table->decimal('compare_price', 12, 2)
                ->nullable()
                ->comment('Original price before discount');

            $table->string('emoji', 10)->nullable();
            $table->string('image_url')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_available')->default(true);

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['restaurant_id', 'category_id']);
            $table->index(['restaurant_id', 'is_available']);
            $table->index(['restaurant_id', 'is_featured']);
            $table->index(['restaurant_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
