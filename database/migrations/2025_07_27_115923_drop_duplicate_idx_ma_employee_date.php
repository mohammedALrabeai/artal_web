<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_attendances', function (Blueprint $table) {
            $table->dropIndex('idx_ma_employee_date');
        });
    }

    public function down(): void
    {
        Schema::table('manual_attendances', function (Blueprint $table) {
            $table->index(
                ['manual_attendance_employee_id', 'date'],
                'idx_ma_employee_date'
            );
        });
    }
};
