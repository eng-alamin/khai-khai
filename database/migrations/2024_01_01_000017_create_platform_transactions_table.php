<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders');
            $table->unsignedInteger('vendor_amount')->comment('Amount due to vendor after commission (BDT paisa)');
            $table->unsignedInteger('rider_amount')->comment('Rider delivery earning (BDT paisa)');
            $table->unsignedInteger('platform_commission')->comment('KhaiKhai gross commission (BDT paisa)');
            $table->decimal('commission_rate', 5, 2)->comment('Applied commission %');
            $table->enum('gateway', ['bkash', 'nagad', 'card', 'cod']);
            $table->string('gateway_txn_id', 80)->unique()->nullable()->comment('Payment gateway transaction ID');
            $table->unsignedInteger('gateway_fee')->default(0)->comment('Gateway charge (BDT paisa)');
            $table->enum('status', ['success', 'failed', 'refunded'])->default('success');
            $table->timestamp('created_at')->useCurrent();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_transactions');
    }
};
