<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // قائمة الجداول التي قد تحتوي على مفاتيح أجنبية تشير إلى roles
        $relatedTables = ['model_has_roles', 'role_has_permissions'];

        foreach ($relatedTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'role_id')) {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = '$table' 
                    AND COLUMN_NAME = 'role_id' 
                    AND CONSTRAINT_NAME != 'PRIMARY'
                ");

                if (!empty($foreignKeys)) {
                    $foreignKeyName = $foreignKeys[0]->CONSTRAINT_NAME;
                    DB::statement("ALTER TABLE $table DROP FOREIGN KEY $foreignKeyName");
                }
            }
        }

        // الآن يمكن حذف جدول roles بأمان
        Schema::dropIfExists('roles');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إعادة إنشاء جدول roles
        // Schema::create('roles', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name')->unique();
        //     $table->timestamps();
        // });

        // // إعادة إضافة المفاتيح الأجنبية في الجداول المرتبطة
        // Schema::table('model_has_roles', function (Blueprint $table) {
        //     if (!Schema::hasColumn('model_has_roles', 'role_id')) {
        //         $table->unsignedBigInteger('role_id')->nullable();
        //     }
        //     $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        // });

        // Schema::table('role_has_permissions', function (Blueprint $table) {
        //     if (!Schema::hasColumn('role_has_permissions', 'role_id')) {
        //         $table->unsignedBigInteger('role_id')->nullable();
        //     }
        //     $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        // });
    }
};
