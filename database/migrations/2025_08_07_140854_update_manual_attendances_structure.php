<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_attendances', function (Blueprint $table) {

       

            /* ⭐️ أعمدة التغطية الفعلية */
            if (! Schema::hasColumn('manual_attendances', 'actual_zone_id')) {
                $table->foreignId('actual_zone_id')
                      ->nullable()
                      ->after('manual_attendance_employee_id')
                      ->constrained('zones')
                      ->nullOnDelete();
            }

            if (! Schema::hasColumn('manual_attendances', 'actual_shift_id')) {
                $table->foreignId('actual_shift_id')
                      ->nullable()
                      ->after('actual_zone_id')
                      ->constrained('shifts')
                      ->nullOnDelete();
            }

            /* ⭐️ الموظّف (الإسناد) الذي تمّت تغطيته إن وُجد */
            if (! Schema::hasColumn('manual_attendances', 'replaced_employee_project_record_id')) {
                $table->foreignId('replaced_employee_project_record_id')
                      ->nullable()
                      ->after('actual_shift_id')
                      ->constrained('employee_project_records')
                      ->nullOnDelete();
            }

            /* ⭐️ مميّز التغطية + الملاحظات + المُنشئ */
            if (! Schema::hasColumn('manual_attendances', 'is_coverage')) {
                $table->boolean('is_coverage')
                      ->default(false)
                      ->after('status');
            }

            if (! Schema::hasColumn('manual_attendances', 'notes')) {
                $table->text('notes')->nullable()->after('is_coverage');
            }

            if (! Schema::hasColumn('manual_attendances', 'created_by')) {
                $table->foreignId('created_by')
                      ->nullable()
                      ->after('notes')
                      ->constrained('users')
                      ->nullOnDelete();
            }

            /* ⭐️ إزالة الحقول القديمة غير الضرورية إن كانت موجودة */
            if (Schema::hasColumn('manual_attendances', 'has_coverage_shift')) {
                $table->dropColumn('has_coverage_shift');
            }
           if (Schema::hasColumn('manual_attendances', 'coverage_employee_id')) {
    // لا نحاول dropForeign لأن القيد غير موجود
    $table->dropColumn('coverage_employee_id');
}
        });
    }

  public function down(): void
{
    Schema::table('manual_attendances', function (Blueprint $table) {

        /* الأعمدة التي تحتوي مفاتيح خارجية فعلًا */
        $fkCols = [
            'actual_zone_id',
            'actual_shift_id',
            'replaced_employee_project_record_id',
            'created_by',
        ];

        /* الأعمدة العادية */
        $plainCols = [
            'is_coverage',
            'notes',
        ];

        /* حذف القيود ثم الأعمدة ذات الـ FK */
        foreach ($fkCols as $col) {
            if (Schema::hasColumn('manual_attendances', $col)) {
                $table->dropForeign([$col]);
                $table->dropColumn($col);
            }
        }

        /* حذف الأعمدة العادية */
        foreach ($plainCols as $col) {
            if (Schema::hasColumn('manual_attendances', $col)) {
                $table->dropColumn($col);
            }
        }

      

        /* إعادة الأعمدة القديمة */
        if (! Schema::hasColumn('manual_attendances', 'has_coverage_shift')) {
            $table->boolean('has_coverage_shift')->nullable();
        }
        if (! Schema::hasColumn('manual_attendances', 'coverage_employee_id')) {
            // لا نحاول dropForeign لأن القيد غير موجود
               try {
                // $table->dropForeign(['coverage_employee_id']);
            } catch (\Throwable $e) {
                // القيد غير موجود؛ تجاهل الخطأ
            }
            $table->unsignedBigInteger('coverage_employee_id')->nullable();
        }
    });
}

};
