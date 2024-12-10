<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // إضافة عمود لحساب عدد ساعات العمل اليومية
            $table->decimal('work_hours', 5, 2)->nullable()->after('check_out');
            
            // إضافة عمود لتسجيل الملاحظات
            $table->text('notes')->nullable()->after('work_hours');

            // إضافة عمود لتحديد ما إذا كان الموظف قد تأخر
            $table->boolean('is_late')->default(false)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // حذف الأعمدة الجديدة عند التراجع
            $table->dropColumn(['work_hours', 'notes', 'is_late']);
        });
    }
};
