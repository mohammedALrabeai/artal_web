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
        Schema::table('employee_project_records', function (Blueprint $table) {
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_project_records', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
            $table->dropColumn('assigned_by');
        });
    }
};
