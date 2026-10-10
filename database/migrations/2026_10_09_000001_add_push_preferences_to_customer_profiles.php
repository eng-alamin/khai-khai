<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer notification settings (mobile app > Profile > Notifications).
     * In-app notifications are always stored; these two only control PUSH.
     */
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->boolean('push_order_updates')->default(true)->after('default_address_id');
            $table->boolean('push_promotions')->default(true)->after('push_order_updates');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn(['push_order_updates', 'push_promotions']);
        });
    }
};
