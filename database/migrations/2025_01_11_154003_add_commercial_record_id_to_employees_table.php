<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommercialRecordIdToEmployeesTable extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('commercial_record_id')->nullable()->after('insurance_company_id'); // عمود معرف الشركة
            $table->foreign('commercial_record_id')->references('id')->on('commercial_records')->onDelete('set null'); // العلاقة مع السجلات التجارية
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['commercial_record_id']);
            $table->dropColumn('commercial_record_id');
        });
    }
}
