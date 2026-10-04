<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Registration and Vendor Settings used two different category lists, so
     * the same cuisine was saved under different names. Move the old spellings
     * to the names in config/delivery.php so the customer filter shows each
     * category once. Only exact old spellings are touched.
     */
    public function up(): void
    {
        DB::table('restaurants')
            ->whereIn('category', ['Bengali Food', 'Bangladeshi'])
            ->update(['category' => 'Bangla Food']);

        DB::table('restaurants')
            ->where('category', 'Desserts & Sweets')
            ->update(['category' => 'Dessert & Sweets']);
    }

    public function down(): void
    {
        // Not reversible: the old spellings were duplicates of the same category.
    }
};
