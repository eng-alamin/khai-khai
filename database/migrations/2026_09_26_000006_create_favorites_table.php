<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer restaurant, food, ba product — je konota save (favorite) rakhte parbe.
     */
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Polymorphic: App\Models\Restaurant / App\Models\Food / App\Models\Product
            $table->morphs('favoritable');

            $table->timestamps();

            $table->unique(['user_id', 'favoritable_type', 'favoritable_id'], 'favorites_unique_per_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
