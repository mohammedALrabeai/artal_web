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
            $table->boolean('exclude_from_absence_report')->default(false)->after('notification_enabled');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_statuses', function (Blueprint $table) {
            //
        });
    }
};
