<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestIdToExclusionsTable extends Migration
{
    public function up()
    {
        Schema::table('exclusions', function (Blueprint $table) {
            $table->unsignedBigInteger('request_id')->nullable()->after('employee_id'); // إضافة العمود
            $table->foreign('request_id')->references('id')->on('requests')->onDelete('set null'); // ربط المفتاح الخارجي
        });
    }

    public function down()
    {
        Schema::table('exclusions', function (Blueprint $table) {
            $table->dropForeign(['request_id']);
            $table->dropColumn('request_id');
        });
    }
}
