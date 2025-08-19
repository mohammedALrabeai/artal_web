<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_statuses', function (Blueprint $table) {
            $table->boolean('is_stationary')->default(false)->after('exclude_from_absence_report')
                  ->comment('هل الجهاز ساكن (لم يتحرك)');
            $table->timestamp('last_movement_at')->nullable()->after('is_stationary')
                  ->comment('آخر وقت تم فيه رصد حركة الجهاز');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_statuses', function (Blueprint $table) {
            $table->dropColumn(['is_stationary', 'last_movement_at']);
        });
    }
};
