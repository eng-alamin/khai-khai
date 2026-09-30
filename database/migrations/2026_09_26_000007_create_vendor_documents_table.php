<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restaurant approve korar age vendor je documents upload kore
     * (trade license, NID, ইত্যাদি) — admin ei table theke review kore.
     */
    public function up(): void
    {
        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();

            $table->enum('type', [
                'trade_license',
                'nid_front',
                'nid_back',
                'tin_certificate',
                'bank_statement',
                'other',
            ]);

            $table->string('file_url', 255);

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->text('rejection_reason')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['restaurant_id', 'type']);
            $table->index(['restaurant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
    }
};
