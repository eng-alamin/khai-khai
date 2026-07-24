<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table) {

            $table->id();

            $table->foreignId('restaurant_id')
                ->nullable()
                ->constrained('restaurants')
                ->nullOnDelete()
                ->comment('null = global default rule');

            $table->enum('type', [
                'percentage',
                'fixed',
            ])->default('percentage');

            $table->decimal('rate', 5, 2)
                ->comment('Commission value');

            $table->date('effective_from');

            $table->date('effective_until')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();


            $table->index([
                'restaurant_id',
                'is_active',
                'effective_from'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
