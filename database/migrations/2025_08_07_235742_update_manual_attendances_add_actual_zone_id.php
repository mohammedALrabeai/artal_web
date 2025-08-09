<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 0) فكّ المفتاح الأجنبي على manual_attendance_employee_id إذا موجود
        Schema::table('manual_attendances', function (Blueprint $table) {
            try {
                // الاسم الافتراضي من لارفيل
                $table->dropForeign(['manual_attendance_employee_id']);
            } catch (\Throwable $e) {
                // تجاهل لو مش موجود
            }
            // 0.1) احذف الـ unique القديم (employee+date)
            try {
                $table->dropUnique('unique_employee_day');
            } catch (\Throwable $e) {
                // تجاهل لو مش موجود
            }
        });

        // 1) أعمدة التغطية الفعلية (تُضاف فقط إن لم تكن موجودة)
        Schema::table('manual_attendances', function (Blueprint $table) {
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
                $table->boolean('is_coverage')->default(false)->after('status');
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

            if (Schema::hasColumn('manual_attendances', 'has_coverage_shift')) {
                $table->dropColumn('has_coverage_shift');
            }
            if (Schema::hasColumn('manual_attendances', 'coverage_employee_id')) {
                $table->dropColumn('coverage_employee_id');
            }
        });

        // 2) أعد المفتاح الأجنبي لعمود manual_attendance_employee_id
        Schema::table('manual_attendances', function (Blueprint $table) {
            try {
                $table->foreign('manual_attendance_employee_id')
                      ->references('id')->on('manual_attendance_employees')
                      ->cascadeOnDelete();
            } catch (\Throwable $e) {
                // ممكن يكون موجود - تجاهل
            }
        });

        // 3) أضف الـ UNIQUE الجديد: (employee + actual_zone + date)
        Schema::table('manual_attendances', function (Blueprint $table) {
            try {
                $table->unique(
                    ['manual_attendance_employee_id', 'actual_zone_id', 'date'],
                    'unique_emp_zone_day'
                );
            } catch (\Throwable $e) {
                // تجاهل لو موجود
            }
        });
    }

public function down(): void
{
    // 1) فكّ جميع المفاتيح الأجنبية التي يمكن أن تعتمد على الـ UNIQUE المركّب
    Schema::table('manual_attendances', function (Blueprint $table) {
        foreach ([
            'manual_attendance_employee_id',
            'actual_zone_id',
            'actual_shift_id',
            'replaced_employee_project_record_id',
            'created_by',
        ] as $col) {
            if (Schema::hasColumn('manual_attendances', $col)) {
                try { $table->dropForeign([$col]); } catch (\Throwable $e) {}
            }
        }
    });

    // 2) الآن احذف الـ UNIQUE المركّب بأمان
    Schema::table('manual_attendances', function (Blueprint $table) {
        try { $table->dropUnique('unique_emp_zone_day'); } catch (\Throwable $e) {}
    });

    // 3) أعد الـ UNIQUE القديم (employee + date)
    Schema::table('manual_attendances', function (Blueprint $table) {
        try {
            $table->unique(['manual_attendance_employee_id', 'date'], 'unique_employee_day');
        } catch (\Throwable $e) {}
    });

    // 4) احذف الأعمدة الجديدة (بما أننا فككنا الـ FKs خلاص)
    Schema::table('manual_attendances', function (Blueprint $table) {
        foreach ([
            'actual_zone_id',
            'actual_shift_id',
            'replaced_employee_project_record_id',
            'created_by',
            'is_coverage',
            'notes',
        ] as $col) {
            if (Schema::hasColumn('manual_attendances', $col)) {
                try { $table->dropColumn($col); } catch (\Throwable $e) {}
            }
        }
    });

    // 5) أعد الـ FK الأساسي لـ manual_attendance_employee_id
    Schema::table('manual_attendances', function (Blueprint $table) {
        try {
            $table->foreign('manual_attendance_employee_id')
                  ->references('id')->on('manual_attendance_employees')
                  ->cascadeOnDelete();
        } catch (\Throwable $e) {}
    });

    // 6) أعد الأعمدة القديمة (لو كانت عندك قبل)
    Schema::table('manual_attendances', function (Blueprint $table) {
        if (!Schema::hasColumn('manual_attendances', 'has_coverage_shift')) {
            $table->boolean('has_coverage_shift')->nullable();
        }
        if (!Schema::hasColumn('manual_attendances', 'coverage_employee_id')) {
            $table->unsignedBigInteger('coverage_employee_id')->nullable();
        }
    });
}

};
