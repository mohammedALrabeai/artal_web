<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // manual_attendance_employees
        Schema::table('manual_attendance_employees', function (Blueprint $table) {
            $table->index('employee_project_record_id', 'idx_mae_project_record');
            $table->index('attendance_month', 'idx_mae_month');
        });

        // employee_project_records
        Schema::table('employee_project_records', function (Blueprint $table) {
            $table->index(['project_id', 'zone_id'], 'idx_epr_project_zone');
        });
    }

    public function down(): void
    {
        Schema::table('manual_attendance_employees', function (Blueprint $table) {
            $table->dropIndex('idx_mae_project_record');
            $table->dropIndex('idx_mae_month');
        });

        Schema::table('employee_project_records', function (Blueprint $table) {
            $table->dropIndex('idx_epr_project_zone');
        });
    }
};
