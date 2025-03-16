<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToLastActiveInEmployeesTable extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            // إضافة الفهرس على العمود last_active
            $table->index('last_active', 'employees_last_active_index');
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            // حذف الفهرس عند التراجع (Rollback)
            $table->dropIndex('employees_last_active_index');
        });
    }
}
