<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // حقول تسجيل الوجه (Enrollment)
            $table->string('face_enrollment_status', 20)->default('not_enrolled');
            $table->timestamp('face_enrolled_at')->nullable();
            $table->timestamp('face_last_update_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'face_enrollment_status',
                'face_enrolled_at',
                'face_last_update_at',
            ]);
        });
    }
};
