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
            $table->foreignId('menu_item_id')->nullable()->constrained('menu_items')->nullOnDelete();
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
