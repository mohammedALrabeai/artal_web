<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employee_statuses', function (Blueprint $table) {
            // إضافة العمود بعد last_present_at (إن وُجد هذا العمود)
            $table->timestamp('last_renewal_at')->nullable()->after('last_present_at');

            // فهرس لتسريع الفلترة حسب آخر تجديد
            $table->index('last_renewal_at', 'employee_statuses_last_renewal_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('employee_statuses', function (Blueprint $table) {
            // حذف الفهرس أولاً ثم العمود
            $table->dropIndex('employee_statuses_last_renewal_at_index');
            $table->dropColumn('last_renewal_at');
        });
    }
};
