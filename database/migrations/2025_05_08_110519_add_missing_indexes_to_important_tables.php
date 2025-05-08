<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // attendances
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['employee_id', 'date'], 'idx_att_employee_date');
            $table->index(['date', 'status'], 'idx_att_date_status');
            $table->index(['employee_id', 'date', 'is_coverage'], 'idx_att_emp_date_coverage');
        });

        // employee_project_records
        Schema::table('employee_project_records', function (Blueprint $table) {
            $table->index(['employee_id', 'end_date'], 'idx_epr_employee_end');
            $table->index(['employee_id', 'zone_id', 'start_date'], 'idx_epr_emp_zone_start');
        });

        // shifts (اختياري)
        Schema::table('shifts', function (Blueprint $table) {
            $table->index(['zone_id', 'start_time', 'end_time'], 'idx_shift_zone_time');
        });
    }

    public function down(): void
    {
        // attendances
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_att_employee_date');
            $table->dropIndex('idx_att_date_status');
            $table->dropIndex('idx_att_emp_date_coverage');
        });

        // employee_project_records
        Schema::table('employee_project_records', function (Blueprint $table) {
            $table->dropIndex('idx_epr_employee_end');
            $table->dropIndex('idx_epr_emp_zone_start');
        });

        // shifts
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('idx_shift_zone_time');
        });
    }
};
