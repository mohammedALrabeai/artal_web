<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            // حذف الحقل القديم إن وُجد
            if (Schema::hasColumn('leaves', 'type')) {
                $table->dropColumn('type');
            }

            // إضافة الحقل الجديد وربطه بجدول leave_types
            $table->foreignId('leave_type_id')
                ->nullable()
                ->constrained('leave_types')
                ->nullOnDelete(); // إذا تم حذف نوع الإجازة تبقى الإجازة بدون نوع
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['leave_type_id']);
            $table->dropColumn('leave_type_id');

            $table->string('type')->nullable(); // استرجاع القديم لو لزم الأمر
        });
    }
};
