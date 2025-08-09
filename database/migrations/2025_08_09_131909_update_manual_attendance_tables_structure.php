<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /** 1) manual_attendances: إسقاط أي FK/Index على الأعمدة ثم حذفها */
        if (Schema::hasTable('manual_attendances')) {
            // إسقاط الـ FK مهما كان اسمها
            $fks = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'manual_attendances'
                  AND COLUMN_NAME IN ('actual_zone_id', 'actual_shift_id')
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            foreach ($fks as $fk) {
                try {
                    DB::statement("ALTER TABLE `manual_attendances` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                } catch (\Throwable $e) {
                    // تجاهل إن لم يوجد
                }
            }

            // إسقاط أي Index مرتبط بالأعمدة
            $idxRows = DB::select("SHOW INDEX FROM `manual_attendances`");
            $toDrop = [];
            foreach ($idxRows as $r) {
                if (in_array($r->Column_name, ['actual_zone_id', 'actual_shift_id'])) {
                    $toDrop[$r->Key_name] = true;
                }
            }
            foreach (array_keys($toDrop) as $idx) {
                try {
                    DB::statement("ALTER TABLE `manual_attendances` DROP INDEX `{$idx}`");
                } catch (\Throwable $e) {
                    // تجاهل إن لم يوجد
                }
            }

            // حذف الأعمدة
            Schema::table('manual_attendances', function (Blueprint $table) {
                if (Schema::hasColumn('manual_attendances', 'actual_zone_id')) {
                    $table->dropColumn('actual_zone_id');
                }
                if (Schema::hasColumn('manual_attendances', 'actual_shift_id')) {
                    $table->dropColumn('actual_shift_id');
                }
            });
        }

        /** 2) manual_attendance_employees: إضافة الأعمدة الجديدة */
        Schema::table('manual_attendance_employees', function (Blueprint $table) {
            if (! Schema::hasColumn('manual_attendance_employees', 'actual_zone_id')) {
                $table->unsignedBigInteger('actual_zone_id')->after('employee_project_record_id');
            }
            if (! Schema::hasColumn('manual_attendance_employees', 'is_main')) {
                $table->boolean('is_main')->default(true)->after('actual_zone_id');
            }
        });

        /** 3) المضي قدمًا: إسقاط أي فهارس قديمة متعارضة إن وُجدت ثم إنشاء الفهرس الفريد الثلاثي */
        $existingIndexes = collect(DB::select("SHOW INDEX FROM `manual_attendance_employees`"))
            ->pluck('Key_name')
            ->unique()
            ->all();

        // أسقط بالاسم إذا كان موجودًا
        foreach (['mae_month_epr_unique', 'mae_month_epr_zone_unique'] as $idxName) {
            if (in_array($idxName, $existingIndexes, true)) {
                try {
                    DB::statement("ALTER TABLE `manual_attendance_employees` DROP INDEX `{$idxName}`");
                } catch (\Throwable $e) {
                    // تجاهل إن فشل الإسقاط (غير موجود فعليًا)
                }
            }
        }

        // الآن أنشئ الفهرس الفريد النهائي
        Schema::table('manual_attendance_employees', function (Blueprint $table) {
            $table->unique(
                ['attendance_month', 'employee_project_record_id', 'actual_zone_id'],
                'mae_month_epr_zone_unique'
            );
        });
    }

    public function down(): void
    {
        /** إزالة الفهرس الفريد والأعمدة من manual_attendance_employees */
        // إسقاط الفهرس إن وجد
        $existingIndexes = collect(DB::select("SHOW INDEX FROM `manual_attendance_employees`"))
            ->pluck('Key_name')
            ->unique()
            ->all();

        if (in_array('mae_month_epr_zone_unique', $existingIndexes, true)) {
            try {
                DB::statement("ALTER TABLE `manual_attendance_employees` DROP INDEX `mae_month_epr_zone_unique`");
            } catch (\Throwable $e) {
                // تجاهل
            }
        }

        Schema::table('manual_attendance_employees', function (Blueprint $table) {
            if (Schema::hasColumn('manual_attendance_employees', 'is_main')) {
                $table->dropColumn('is_main');
            }
            if (Schema::hasColumn('manual_attendance_employees', 'actual_zone_id')) {
                $table->dropColumn('actual_zone_id');
            }
        });

        /** إعادة الأعمدة إلى manual_attendances (nullable للسلامة) */
        Schema::table('manual_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('manual_attendances', 'actual_zone_id')) {
                $table->unsignedBigInteger('actual_zone_id')->nullable()->after('employee_project_record_id');
            }
            if (! Schema::hasColumn('manual_attendances', 'actual_shift_id')) {
                $table->unsignedBigInteger('actual_shift_id')->nullable()->after('actual_zone_id');
            }
        });
    }
};
