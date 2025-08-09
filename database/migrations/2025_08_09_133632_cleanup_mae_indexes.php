<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('manual_attendance_employees')) {
            return;
        }

        // جمع أسماء الفهارس الحالية
        $existing = collect(DB::select("SHOW INDEX FROM `manual_attendance_employees`"))
            ->pluck('Key_name')
            ->unique()
            ->all();

        // 1) إسقاط الفهرس الفريد القديم (يتعارض منطقيًا مع وجود actual_zone_id)
        if (in_array('unique_employee_month', $existing, true)) {
            Schema::table('manual_attendance_employees', function (Blueprint $table) {
                $table->dropUnique('unique_employee_month');
            });
        }

        // 2) إسقاط الفهرس المركب الزائد (attendance_month, employee_project_record_id)
        if (in_array('idx_mae_month_record', $existing, true)) {
            Schema::table('manual_attendance_employees', function (Blueprint $table) {
                $table->dropIndex('idx_mae_month_record');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('manual_attendance_employees')) {
            return;
        }

        // إعادة إنشاء الفهرس الفريد القديم
        Schema::table('manual_attendance_employees', function (Blueprint $table) {
            // قد يفشل لو عندك بيانات متعارضة بعد التغيير الجديد
            $table->unique(
                ['employee_project_record_id', 'attendance_month'],
                'unique_employee_month'
            );
        });

        // إعادة إنشاء الفهرس المركب الزائد (لإتمام down بشكل مماثل للوضع السابق)
        Schema::table('manual_attendance_employees', function (Blueprint $table) {
            $table->index(
                ['attendance_month', 'employee_project_record_id'],
                'idx_mae_month_record'
            );
        });
    }
};
