<?php

// database/migrations/YYYY_MM_DD_add_coverage_employee_id_to_manual_attendances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** يشغَّل عند التقدُّم للأمام */
    public function up(): void
    {
        Schema::table('manual_attendances', function (Blueprint $table) {

            // أضف العمود فقط إذا لم يكن موجودًا
            if (! Schema::hasColumn('manual_attendances', 'coverage_employee_id')) {
                $table->foreignId('coverage_employee_id')
                      ->nullable()
                      ->constrained('employees')
                      ->nullOnDelete();
            }
        });
    }

    /** يشغَّل عند التراجع backward */
    public function down(): void
    {
        Schema::table('manual_attendances', function (Blueprint $table) {

            // احذف القيد ثم العمود إذا كانا موجوديْن
            if (Schema::hasColumn('manual_attendances', 'coverage_employee_id')) {

                // قد يكون القيد موجودًا أو لا؛ جرِّب حذفه بحماية
                try {
                    $table->dropForeign(['coverage_employee_id']);
                } catch (\Throwable $e) {
                    // القيد غير موجود؛ تجاهل
                }

                $table->dropColumn('coverage_employee_id');
            }
        });
    }
};
