<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveSponsorCompanyFromEmployeesTable extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('sponsor_company'); // حذف العمود
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('sponsor_company')->nullable(); // إعادة العمود في حالة التراجع
        });
    }
}
