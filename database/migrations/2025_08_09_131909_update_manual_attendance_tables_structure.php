<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /**
         * 1) manual_attendances:
         *    إسقاط أي FK/Index على الأعمدة ثم حذفها "فقط إذا كانت موجودة"،
         *    مع try/catch لتفادي 1072 حتى لو حصل اختلاف حالة بين الكاش/المخطط.
         */
        if (Schema::hasTable('manual_attendances')) {

            // حصر الأعمدة التي ننوي إسقاطها
            $candidateCols = ['actual_zone_id', 'actual_shift_id'];

            // معرفة الأعمدة الموجودة فعليًا الآن
            $existingCols = collect(DB::select("
                SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'manual_attendances'
                  AND COLUMN_NAME IN ('actual_zone_id','actual_shift_id')
            "))->pluck('COLUMN_NAME')->all();

            // دالة لإسقاط FK/Index ثم العمود بأمان
            $dropColSafely = function (string $col) {
                // 1) إسقاط أي FK على هذا العمود
                $fks = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'manual_attendances'
                      AND COLUMN_NAME = ?
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                ", [$col]);

                foreach ($fks as $fk) {
                    try {
                        DB::statement("ALTER TABLE `manual_attendances` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                    } catch (\Throwable $e) { /* تجاهل */ }
                }

                // 2) إسقاط أي فهارس على نفس العمود
                $idxRows = DB::select("SHOW INDEX FROM `manual_attendances`");
                $toDrop = [];
                foreach ($idxRows as $r) {
                    $colName = $r->Column_name ?? null;
                    $keyName = $r->Key_name ?? null;
                    if ($colName === $col && $keyName) {
                        $toDrop[$keyName] = true;
                    }
                }
                foreach (array_keys($toDrop) as $idx) {
                    try {
                        DB::statement("ALTER TABLE `manual_attendances` DROP INDEX `{$idx}`");
                    } catch (\Throwable $e) { /* تجاهل */ }
                }

                // 3) إسقاط العمود نفسه (مع فحص إضافي + try/catch)
                try {
                    $exists = DB::selectOne("
                        SELECT 1 AS e
                        FROM information_schema.COLUMNS
                        WHERE TABLE_SCHEMA = DATABASE()
                          AND TABLE_NAME = 'manual_attendances'
                          AND COLUMN_NAME = ?
                        LIMIT 1
                    ", [$col]);

                    if ($exists) {
                        DB::statement("ALTER TABLE `manual_attendances` DROP COLUMN `{$col}`");
                    }
                } catch (\Throwable $e) {
                    // تجاهل 1072 أو أي اختلاف حالة
                }
            };

            // تنفيذ الإسقاط فقط للأعمدة الموجودة فعلًا
            foreach ($candidateCols as $col) {
                if (in_array($col, $existingCols, true)) {
                    $dropColSafely($col);
                }
            }
        }

        /**
         * 2) manual_attendance_employees:
         *    إضافة الأعمدة الجديدة إن لم تكن موجودة.
         */
        if (Schema::hasTable('manual_attendance_employees')) {
            Schema::table('manual_attendance_employees', function (Blueprint $table) {
                if (! Schema::hasColumn('manual_attendance_employees', 'actual_zone_id')) {
                    $table->unsignedBigInteger('actual_zone_id')->after('employee_project_record_id');
                }
                if (! Schema::hasColumn('manual_attendance_employees', 'is_main')) {
                    $table->boolean('is_main')->default(true)->after('actual_zone_id');
                }
            });

            // 3) إنشاء الفهرس الفريد الثلاثي بعد إسقاط أي أسماء متضاربة إن وُجدت
            $existingIndexes = collect(DB::select("SHOW INDEX FROM `manual_attendance_employees`"))
                ->pluck('Key_name')
                ->unique()
                ->all();

            foreach (['mae_month_epr_unique', 'mae_month_epr_zone_unique'] as $idxName) {
                if (in_array($idxName, $existingIndexes, true)) {
                    try {
                        DB::statement("ALTER TABLE `manual_attendance_employees` DROP INDEX `{$idxName}`");
                    } catch (\Throwable $e) { /* تجاهل */ }
                }
            }

            Schema::table('manual_attendance_employees', function (Blueprint $table) {
                $table->unique(
                    ['attendance_month', 'employee_project_record_id', 'actual_zone_id'],
                    'mae_month_epr_zone_unique'
                );
            });
        }
    }

    public function down(): void
    {
        // إزالة الفهرس الفريد والأعمدة من manual_attendance_employees
        if (Schema::hasTable('manual_attendance_employees')) {
            $existingIndexes = collect(DB::select("SHOW INDEX FROM `manual_attendance_employees`"))
                ->pluck('Key_name')
                ->unique()
                ->all();

            if (in_array('mae_month_epr_zone_unique', $existingIndexes, true)) {
                try {
                    DB::statement("ALTER TABLE `manual_attendance_employees` DROP INDEX `mae_month_epr_zone_unique`");
                } catch (\Throwable $e) { /* تجاهل */ }
            }

            Schema::table('manual_attendance_employees', function (Blueprint $table) {
                if (Schema::hasColumn('manual_attendance_employees', 'is_main')) {
                    $table->dropColumn('is_main');
                }
                if (Schema::hasColumn('manual_attendance_employees', 'actual_zone_id')) {
                    $table->dropColumn('actual_zone_id');
                }
            });
        }

        // إعادة الأعمدة إلى manual_attendances (Nullable للسلامة)
        if (Schema::hasTable('manual_attendances')) {
            Schema::table('manual_attendances', function (Blueprint $table) {
                if (! Schema::hasColumn('manual_attendances', 'actual_zone_id')) {
                    $table->unsignedBigInteger('actual_zone_id')->nullable()->after('employee_project_record_id');
                }
                if (! Schema::hasColumn('manual_attendances', 'actual_shift_id')) {
                    $table->unsignedBigInteger('actual_shift_id')->nullable()->after('actual_zone_id');
                }
            });
        }
    }
};
