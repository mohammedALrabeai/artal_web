<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exclusions', function (Blueprint $table) {
            $table->foreignId('employee_project_record_id')
                  ->nullable()
                  ->constrained('employee_project_records')
                  ->nullOnDelete(); // أو ->cascadeOnDelete() حسب سياستك
        });
    }

    public function down(): void
    {
        Schema::table('exclusions', function (Blueprint $table) {
            $table->dropForeign(['employee_project_record_id']);
            $table->dropColumn('employee_project_record_id');
        });
    }
};
