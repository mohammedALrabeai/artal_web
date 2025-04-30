<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_statuses', function (Blueprint $table) {
            $table->unsignedTinyInteger('consecutive_absence_count')->default(0)->after('employee_id');
            $table->date('last_present_at')->nullable()->after('consecutive_absence_count');
        });
    }

    public function down(): void
    {
        Schema::table('employee_statuses', function (Blueprint $table) {
            $table->dropColumn('consecutive_absence_count');
            $table->dropColumn('last_present_at');
        });
    }
};
