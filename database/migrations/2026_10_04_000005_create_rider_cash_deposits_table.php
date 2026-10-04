<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cash a rider hands over to the platform.
 *
 * With Cash on Delivery the rider collects the customer's money. What he
 * still has to give back is:  cash collected (delivered COD orders)
 *                           - deposits recorded here.
 * An admin records a deposit only after really receiving the money.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_cash_deposits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rider_id')->constrained('users');
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['cash', 'bkash', 'bank_transfer'])->default('cash');
            $table->string('reference', 100)->nullable()->comment('bKash TrxID / bank slip no.');
            $table->string('note', 255)->nullable();

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deposited_at')->useCurrent();
            $table->timestamps();

            $table->index(['rider_id', 'deposited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_cash_deposits');
    }
};
