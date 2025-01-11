<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotesToMunicipalLicensesTable extends Migration
{
    public function up()
    {
        Schema::table('municipal_licenses', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('vat'); // عمود الملاحظات
        });
    }

    public function down()
    {
        Schema::table('municipal_licenses', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
}
