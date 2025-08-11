<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
public function up(): void
{
    // 0) إسقاط أي فهرس فريد مركّب على (asset_id, is_open) إن وُجد (بأي اسم)
    $rows = \DB::select("SHOW INDEX FROM `asset_assignments`");
    $indexes = [];
    foreach ($rows as $r) {
        $key = $r->Key_name;
        $indexes[$key]['non_unique'] = (int) $r->Non_unique; // 0 يعني Unique
        $indexes[$key]['columns'][]  = $r->Column_name;
    }

    foreach ($indexes as $name => $meta) {
        $cols = $meta['columns'] ?? [];
        sort($cols);
        $isUnique = ($meta['non_unique'] === 0);
        if ($isUnique && $cols === ['asset_id', 'is_open']) {
            \DB::statement("ALTER TABLE `asset_assignments` DROP INDEX `{$name}`");
        }
    }

    // 1) إضافة عمود open_asset_id (عادي)
    \Schema::table('asset_assignments', function (\Illuminate\Database\Schema\Blueprint $table) {
        if (! \Schema::hasColumn('asset_assignments', 'open_asset_id')) {
            $table->unsignedBigInteger('open_asset_id')->nullable()->after('is_open');
        }
    });

    // 2) تهيئة القيم الحالية
    \DB::statement("
        UPDATE asset_assignments
        SET open_asset_id = CASE WHEN returned_date IS NULL THEN asset_id ELSE NULL END
    ");

    // 3) إضافة فهرس فريد على open_asset_id إن لم يكن موجوداً، وفهرس على returned_date
    $rows2 = \DB::select("SHOW INDEX FROM `asset_assignments`");
    $singleIndexes = [];
    foreach ($rows2 as $r) {
        $k = $r->Key_name;
        $singleIndexes[$k]['non_unique'] = (int) $r->Non_unique;
        $singleIndexes[$k]['columns'][]  = $r->Column_name;
    }

    $hasUniqueOpenAssetId = false;
    $hasReturnedDateIndex = false;
    foreach ($singleIndexes as $name => $meta) {
        $cols = $meta['columns'] ?? [];
        sort($cols);
        if ($meta['non_unique'] === 0 && $cols === ['open_asset_id']) {
            $hasUniqueOpenAssetId = true;
        }
        if (in_array('returned_date', $cols, true)) {
            $hasReturnedDateIndex = true;
        }
    }

    \Schema::table('asset_assignments', function (\Illuminate\Database\Schema\Blueprint $table) use ($hasUniqueOpenAssetId, $hasReturnedDateIndex) {
        if (! $hasUniqueOpenAssetId) {
            $table->unique('open_asset_id', 'asset_assignments_open_asset_id_unique');
        }
        if (! $hasReturnedDateIndex) {
            $table->index('returned_date', 'asset_assignments_returned_date_index');
        }
    });

    // 4) إعادة إنشاء التريجرات بشكل idempotent
    \DB::unprepared("DROP TRIGGER IF EXISTS bi_asset_assignments_open_asset_id");
    \DB::unprepared("DROP TRIGGER IF EXISTS bu_asset_assignments_open_asset_id");

    \DB::unprepared("
        CREATE TRIGGER bi_asset_assignments_open_asset_id
        BEFORE INSERT ON asset_assignments
        FOR EACH ROW
        SET NEW.open_asset_id = CASE
            WHEN NEW.returned_date IS NULL THEN NEW.asset_id
            ELSE NULL
        END
    ");

    \DB::unprepared("
        CREATE TRIGGER bu_asset_assignments_open_asset_id
        BEFORE UPDATE ON asset_assignments
        FOR EACH ROW
        SET NEW.open_asset_id = CASE
            WHEN NEW.returned_date IS NULL THEN NEW.asset_id
            ELSE NULL
        END
    ");
}



    public function down(): void
    {
        // حذف التريجرات
        DB::unprepared("DROP TRIGGER IF EXISTS bi_asset_assignments_open_asset_id");
        DB::unprepared("DROP TRIGGER IF EXISTS bu_asset_assignments_open_asset_id");

        // إزالة الفهارس والعمود
        Schema::table('asset_assignments', function (Blueprint $table) {
            try { $table->dropUnique('asset_assignments_open_asset_id_unique'); } catch (\Throwable $e) {}
            try { $table->dropIndex(['returned_date']); } catch (\Throwable $e) {}

            if (Schema::hasColumn('asset_assignments', 'open_asset_id')) {
                $table->dropColumn('open_asset_id');
            }
        });

        // (اختياري) إعادة القيد القديم
        // Schema::table('asset_assignments', function (Blueprint $table) {
        //     $table->unique(['asset_id', 'is_open'], 'unique_open_assignment_per_asset');
        // });
    }
};
