<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveVacationBalanceFromEmployeesTable extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('vacation_balance'); // حذف العمود
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('vacation_balance', 8, 2)->default(0); // إعادة العمود إذا تم التراجع
        });
    }
}
