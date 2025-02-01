<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * تشغيل الهجرة (Migration).
     */
    public function up(): void
    {
        // **إزالة المفتاح الأجنبي من جدول user_roles قبل حذف جدول roles**
        if (Schema::hasTable('user_roles')) {
            Schema::table('user_roles', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
                $table->dropForeign(['user_id']);
            });
        }

        // حذف جدول الربط بين المستخدمين والأدوار
        if (Schema::hasTable('user_roles')) {
            Schema::dropIfExists('user_roles');
        }

        // **إزالة المفتاح الأجنبي من جدول users قبل حذف العمود**
        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            });
        }

        // حذف جدول الأدوار إذا كان موجودًا
        if (Schema::hasTable('roles')) {
            Schema::dropIfExists('roles');
        }

        // حذف الجداول المرتبطة بالأدوار والصلاحيات
        $tablesToDelete = [
            'role_has_permissions',
            'model_has_roles',
            'model_has_permissions',
            'permissions'
        ];

        foreach ($tablesToDelete as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
            }
        }
    }

    /**
     * التراجع عن الهجرة (Rollback).
     */
    public function down(): void
    {
        // إعادة إنشاء جدول الأدوار
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // إعادة إنشاء جدول الربط بين المستخدمين والأدوار
        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
        });

        // إعادة إضافة عمود "role_id" إذا تم حذفه
        if (!Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('cascade');
            });
        }
    }
};
