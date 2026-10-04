<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_popups', function (Blueprint $table) {
            $table->id();
            $table->string('badge', 60)->nullable();            // 🎉 বিশেষ অফার
            $table->string('highlight', 60);                    // ৪০%–৫০%
            $table->string('highlight_sub', 60)->nullable();    // ডিসকাউন্ট
            $table->string('title', 120);                       // সাথে ফ্রি ডেলিভারি!
            $table->text('message')->nullable();                // ছোট বর্ণনা
            $table->string('chips', 255)->nullable();           // comma separated: রেস্টুরেন্ট, বাজার
            $table->string('button_text', 60)->default('এখনই অর্ডার করুন');
            $table->string('button_url', 255)->nullable();      // blank = restaurants page
            $table->unsignedInteger('repeat_hours')->default(24); // 0 = protibar dekhabe
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_popups');
    }
};
