<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 3 level category tree:
     *   Category (parent_id = null)  ->  Sub Category  ->  Child Category
     * type column ekhon food, product, bazar, service (string(20), kono schema change lagbe na).
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('categories')
                ->nullOnDelete();

            $table->index(['type', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['type', 'parent_id']);
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
