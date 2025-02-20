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
        Schema::table('employee_devices', function (Blueprint $table) {
            $table->dropUnique(['device_id']); // إزالة القيود الفريدة عن device_id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_devices', function (Blueprint $table) {
            $table->unique('device_id'); // إعادة القيود الفريدة إذا تم التراجع عن المهاجرة
        });
    }
};
