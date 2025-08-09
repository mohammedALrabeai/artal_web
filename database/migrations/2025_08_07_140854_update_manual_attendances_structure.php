<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_attendances', function (Blueprint $table) {
            /* ⭐️ أعمدة التغطية/الملاحظات/المنشئ (إضافات) */
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

            if (! Schema::hasColumn('manual_attendances', 'replaced_employee_project_record_id')) {
                $table->foreignId('replaced_employee_project_record_id')
                    ->nullable()
                    ->after('actual_shift_id')
                    ->constrained('employee_project_records')
                    ->nullOnDelete();
            }

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
        });

        // ⭐️ إسقاط الأعمدة/القيود القديمة بأمان خارج الـ Blueprint لتفادي خطأ 1553
        if (Schema::hasTable('manual_attendances')) {
            // 1) إسقاط FK على coverage_employee_id (مهما كان الاسم)
            $fks = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'manual_attendances'
                  AND COLUMN_NAME = 'coverage_employee_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            foreach ($fks as $fk) {
                try {
                    DB::statement("ALTER TABLE `manual_attendances` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
                } catch (\Throwable $e) { /* تجاهل */ }
            }

            // 2) إسقاط أي فهارس على coverage_employee_id (قد ينشئها MySQL تلقائياً مع الـ FK)
            $idxRows = DB::select("SHOW INDEX FROM `manual_attendances`");
            $toDrop = [];
            foreach ($idxRows as $r) {
                // بعض الإصدارات تُرجع الحقول بحروف مختلفة
                $col = $r->Column_name ?? $r->ColumnName ?? null;
                $key = $r->Key_name    ?? $r->KeyName    ?? null;
                if ($col === 'coverage_employee_id' && $key) {
                    $toDrop[$key] = true;
                }
            }
            foreach (array_keys($toDrop) as $idxName) {
                try {
                    DB::statement("ALTER TABLE `manual_attendances` DROP INDEX `{$idxName}`");
                } catch (\Throwable $e) { /* تجاهل */ }
            }

            // 3) إسقاط الأعمدة القديمة إن وُجدت
            Schema::table('manual_attendances', function (Blueprint $table) {
                if (Schema::hasColumn('manual_attendances', 'has_coverage_shift')) {
                    $table->dropColumn('has_coverage_shift');
                }
                if (Schema::hasColumn('manual_attendances', 'coverage_employee_id')) {
                    $table->dropColumn('coverage_employee_id');
                }
            });
        }
    }

    public function down(): void
    {
        // إعادة ما أُضيف في up(): إسقاط الإضافات أولاً
        Schema::table('manual_attendances', function (Blueprint $table) {
            $fkCols = [
                'actual_zone_id',
                'actual_shift_id',
                'replaced_employee_project_record_id',
                'created_by',
            ];
            $plainCols = ['is_coverage', 'notes'];

            foreach ($fkCols as $col) {
                if (Schema::hasColumn('manual_attendances', $col)) {
                    // إسقاط FK إن كان موجودًا
                    try { $table->dropForeign([$col]); } catch (\Throwable $e) {}
                }
            }
            foreach ($fkCols as $col) {
                if (Schema::hasColumn('manual_attendances', $col)) {
                    $table->dropColumn($col);
                }
            }

            foreach ($plainCols as $col) {
                if (Schema::hasColumn('manual_attendances', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // إعادة الأعمدة القديمة (بدون إنشاء FK لأنك لا تريده الآن)
        Schema::table('manual_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('manual_attendances', 'has_coverage_shift')) {
                $table->boolean('has_coverage_shift')->nullable();
            }
            if (! Schema::hasColumn('manual_attendances', 'coverage_employee_id')) {
                $table->unsignedBigInteger('coverage_employee_id')->nullable();
            }
        });
    }
};
