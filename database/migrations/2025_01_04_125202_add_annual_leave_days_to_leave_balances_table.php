<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAnnualLeaveDaysToLeaveBalancesTable extends Migration
{
    public function up()
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->decimal('annual_leave_days', 8, 2)->default(0)->after('leave_type');
        });
    }

    public function down()
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropColumn('annual_leave_days');
        });
    }
}
