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
            $table->string('item_name', 120)->comment('Snapshot: name at order time');
            $table->unsignedInteger('item_price')->comment('Snapshot: unit price at order time (BDT paisa)');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('line_total')->comment('item_price × quantity');
            $table->string('emoji', 10)->nullable()->comment('Snapshot emoji');
            // No timestamps needed — order creation is the timestamp

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
