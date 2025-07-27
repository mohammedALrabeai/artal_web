<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         *  🔹 manual_attendance_employees
         *  فهرس مركّب يسرّع جلب صفوف شهرٍ ما لمجموعة كبيرة من الأسناد.
         *  لاحظ أن وجود فهرس مفرد على attendance_month لا يتعارض؛
         *  MySQL يستعمل الأقرب إلى خطة التنفيذ.
         */
        Schema::table('manual_attendance_employees', function (Blueprint $table) {
            $table->index(
                ['attendance_month', 'employee_project_record_id'],
                'idx_mae_month_record'
            );
        });

        /*
         *  🔹 manual_attendances
         *  يسرّع الفلترة بـ employee ثم history الشهرى / اليومى.
         */
        Schema::table('manual_attendances', function (Blueprint $table) {
            $table->index(
                ['manual_attendance_employee_id', 'date'],
                'idx_ma_employee_date'
            );
        });
    }

    public function down(): void
    {
        Schema::table('manual_attendance_employees', function (Blueprint $table) {
            $table->dropIndex('idx_mae_month_record');
        });

        Schema::table('manual_attendances', function (Blueprint $table) {
            $table->dropIndex('idx_ma_employee_date');
        });
    }
};
