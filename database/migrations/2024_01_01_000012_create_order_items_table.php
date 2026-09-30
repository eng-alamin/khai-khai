<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Polymorphic: vendor er order hole 'App\Models\Food', admin er
            // order hole 'App\Models\Product'. No FK constraint (cross-table),
            // item delete hoye gele o snapshot column gulo diye order history thake.
            $table->nullableMorphs('orderable');

            $table->string('item_image')->nullable();
            $table->string('item_name', 120)->comment('Snapshot: name at order time');
            $table->decimal('item_price', 15, 2)->comment('Snapshot: unit price at order time');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('discount_amount',15,2)->default(0);
            $table->decimal('line_total', 15, 2)->comment('(item_price × quantity) - discount');
            $table->string('emoji', 10)->nullable()->comment('Snapshot emoji');
            $table->json('options')->nullable()->comment('Snapshot of variants and addons');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
