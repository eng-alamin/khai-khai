<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes finance structures that nothing in the application uses:
 *
 *  - vendor_payouts            : duplicate of `payouts` (the Payout model).
 *  - commission_rules          : never read or written by any code. The real
 *                                rate is restaurants.commission_rate with the
 *                                "default_commission_rate" admin setting as
 *                                fallback (see CommissionService).
 *  - vendor_settings.commission_rate     : never read or written.
 *  - vendor_settings.is_accepting_orders : duplicate of restaurants.is_open.
 *
 * Safe-guard: if either table already contains rows the migration stops
 * BEFORE changing anything, so no data can be lost silently.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['vendor_payouts', 'commission_rules'] as $tableName) {
            if (Schema::hasTable($tableName) && DB::table($tableName)->exists()) {
                throw new RuntimeException(
                    "Table [{$tableName}] contains data. Export/review it first, then drop it manually."
                );
            }
        }

        Schema::dropIfExists('vendor_payouts');
        Schema::dropIfExists('commission_rules');

        if (Schema::hasColumn('vendor_settings', 'is_accepting_orders')) {
            if (Schema::hasIndex('vendor_settings', ['restaurant_id', 'is_accepting_orders'])) {
                Schema::table('vendor_settings', function (Blueprint $table) {
                    $table->dropIndex(['restaurant_id', 'is_accepting_orders']);
                });
            }

            Schema::table('vendor_settings', function (Blueprint $table) {
                $table->dropColumn('is_accepting_orders');
            });
        }

        if (Schema::hasColumn('vendor_settings', 'commission_rate')) {
            Schema::table('vendor_settings', function (Blueprint $table) {
                $table->dropColumn('commission_rate');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('vendor_settings', 'commission_rate')) {
            Schema::table('vendor_settings', function (Blueprint $table) {
                $table->decimal('commission_rate', 5, 2)
                    ->default(0)
                    ->after('min_order_amount')
                    ->comment('Restaurant commission percentage snapshot');
            });
        }

        if (! Schema::hasColumn('vendor_settings', 'is_accepting_orders')) {
            Schema::table('vendor_settings', function (Blueprint $table) {
                $table->boolean('is_accepting_orders')
                    ->default(true)
                    ->after('commission_rate')
                    ->comment('Restaurant online/offline status');

                $table->index(['restaurant_id', 'is_accepting_orders']);
            });
        }

        if (! Schema::hasTable('vendor_payouts')) {
            Schema::create('vendor_payouts', function (Blueprint $table) {
                $table->id();
                $table->string('payout_number', 30)->unique();
                $table->foreignId('restaurant_id')->constrained('restaurants');
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('gross_sales', 15, 2);
                $table->decimal('platform_fee', 15, 2);
                $table->decimal('net_payout', 15, 2);
                $table->json('payment_account_snapshot')->nullable();
                $table->enum('method', ['bkash', 'bank_transfer']);
                $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'on_hold'])
                    ->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['restaurant_id', 'period_start', 'period_end']);
                $table->index(['restaurant_id', 'status']);
            });
        }

        if (! Schema::hasTable('commission_rules')) {
            Schema::create('commission_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')
                    ->nullable()
                    ->constrained('restaurants')
                    ->nullOnDelete()
                    ->comment('null = global default rule');
                $table->enum('type', ['percentage', 'fixed'])->default('percentage');
                $table->decimal('rate', 5, 2)->comment('Commission value');
                $table->date('effective_from');
                $table->date('effective_until')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['restaurant_id', 'is_active', 'effective_from']);
            });
        }
    }
};
