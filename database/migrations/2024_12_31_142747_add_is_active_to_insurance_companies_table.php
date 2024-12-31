<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToInsuranceCompaniesTable extends Migration
{
    public function up()
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('branch'); // مضاف بعد عمود 'branch'
        });
    }

    public function down()
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
}
