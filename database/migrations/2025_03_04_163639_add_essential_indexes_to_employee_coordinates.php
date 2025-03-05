<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employee_coordinates', function (Blueprint $table) {
            // ✅ فهرس على `employee_id` لتسريع البحث عن مواقع الموظفين
            $table->index('employee_id');

            // ✅ فهرس على `timestamp` لتسريع البحث عن أحدث الإحداثيات لكل موظف
            $table->index('timestamp');

            // ✅ فهرس مركب لتسريع البحث عن أحدث موقع للموظف في وقت معين
            $table->index(['employee_id', 'timestamp']);
        });
    }

    public function down()
    {
        Schema::table('employee_coordinates', function (Blueprint $table) {
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['timestamp']);
            $table->dropIndex(['employee_id', 'timestamp']);
        });
    }
};
