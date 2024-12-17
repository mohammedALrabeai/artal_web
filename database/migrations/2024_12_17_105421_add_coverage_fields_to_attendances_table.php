<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('is_coverage')->default(false); // يحدد إذا كان الحضور طلب تغطية
            $table->foreignId('coverage_id')->nullable()->constrained('coverages')->onDelete('set null'); // ربط جدول التغطيات
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending'); // حالة طلب التغطية
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            //
        });
    }
};
