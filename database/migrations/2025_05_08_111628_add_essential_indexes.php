<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول attendances
        Schema::table('attendances', function (Blueprint $table) {
            if (! $this->hasIndex('attendances', 'idx_att_employee_date')) {
                $table->index(['employee_id', 'date'], 'idx_att_employee_date');
            }

            if (! $this->hasIndex('attendances', 'idx_att_date_status')) {
                $table->index(['date', 'status'], 'idx_att_date_status');
            }

            if (! $this->hasIndex('attendances', 'idx_att_emp_date_coverage')) {
                $table->index(['employee_id', 'date', 'is_coverage'], 'idx_att_emp_date_coverage');
            }
        });

        Schema::table('employee_project_records', function (Blueprint $table) {
            if (! $this->hasIndex('employee_project_records', 'idx_epr_employee_end')) {
                $table->index(['employee_id', 'end_date'], 'idx_epr_employee_end');
            }

            if (! $this->hasIndex('employee_project_records', 'idx_epr_emp_zone_start')) {
                $table->index(['employee_id', 'zone_id', 'start_date'], 'idx_epr_emp_zone_start');
            }
        });

        Schema::table('zones', function (Blueprint $table) {
            if (! $this->hasIndex('zones', 'idx_zones_status')) {
                $table->index('status', 'idx_zones_status');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (! $this->hasIndex('employees', 'idx_employees_mobile')) {
                $table->index('mobile_number', 'idx_employees_mobile');
            }
        });
    }

    // أضف هذه الدالة داخل نفس الكلاس
    protected function hasIndex(string $table, string $index): bool
    {
        return Schema::hasTable($table) &&
            collect(DB::select("SHOW INDEXES FROM {$table}"))
                ->pluck('Key_name')
                ->contains($index);
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_att_employee_date');
            $table->dropIndex('idx_att_date_status');
            $table->dropIndex('idx_att_emp_date_coverage');
        });

        Schema::table('employee_project_records', function (Blueprint $table) {
            $table->dropIndex('idx_epr_employee_end');
            $table->dropIndex('idx_epr_emp_zone_start');
        });

        Schema::table('zones', function (Blueprint $table) {
            $table->dropIndex('idx_zones_status');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_employees_mobile');
        });
    }
};
