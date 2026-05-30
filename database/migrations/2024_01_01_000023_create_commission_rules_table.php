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
            $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->cascadeOnDelete()
                ->comment('null = global default rule');
            $table->decimal('rate', 5, 2)->comment('Commission % e.g. 15.00');
            $table->date('effective_from');
            $table->date('effective_until')->nullable()->comment('null = indefinite');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index('restaurant_id');
            $table->index(['restaurant_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
