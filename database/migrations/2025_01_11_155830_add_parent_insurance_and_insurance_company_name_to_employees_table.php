<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentInsuranceAndInsuranceCompanyNameToEmployeesTable extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('parent_insurance')->nullable()->after('insurance_end_date'); // تأمين الوالدين
            $table->string('insurance_company_name')->nullable()->after('parent_insurance'); // اسم شركة التأمين
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['parent_insurance', 'insurance_company_name']);
        });
    }
}
