<?php

// database/migrations/YYYY_MM_DD_add_coverage_employee_id_to_manual_attendances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_attendances', function (Blueprint $table) {
            $table->foreignId('coverage_employee_id')->nullable()->constrained('employees')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('manual_attendances', function (Blueprint $table) {
            $table->dropForeign(['coverage_employee_id']);
            $table->dropColumn('coverage_employee_id');
        });
    }
};
