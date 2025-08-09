<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إسقاط الفهرس الفريد القديم (إن وُجد) لمنع التعارض مع الفهرس الثلاثي الحالي
        if (Schema::hasTable('manual_attendance_employees')) {
            $existing = collect(DB::select("SHOW INDEX FROM `manual_attendance_employees`"))
                ->pluck('Key_name')
                ->unique()
                ->all();

            if (in_array('unique_employee_month', $existing, true)) {
                DB::statement("ALTER TABLE `manual_attendance_employees` DROP INDEX `unique_employee_month`");
            }
        }

        // ✅ لا تغييرات أخرى هنا حسب طلبك
    }

    public function down(): void
    {
        // إعادة إنشاء الفهرس الفريد كما كان (employee_project_record_id, attendance_month)
        // ملاحظة: سيُفشل الإنشاء إذا وُجد تعارض مع بياناتك الحالية.
        Schema::table('manual_attendance_employees', function (Blueprint $table) {
            // للتحكم في الاسم القديم نفسه
            $table->unique(
                ['employee_project_record_id', 'attendance_month'],
                'unique_employee_month'
            );
        });
    }
};
