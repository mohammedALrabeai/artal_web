<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestIdToAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::table('attachments', function (Blueprint $table) {
            // حذف المفتاح الأجنبي المرتبط بـ exclusion_id
            $table->dropForeign(['exclusion_id']);
            
            // حذف العمود exclusion_id
            $table->dropColumn('exclusion_id');

            // إضافة عمود request_id
            $table->unsignedBigInteger('request_id')->nullable()->after('employee_id');
            $table->foreign('request_id')->references('id')->on('requests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('attachments', function (Blueprint $table) {
            // حذف المفتاح الأجنبي المرتبط بـ request_id
            $table->dropForeign(['request_id']);
            
            // حذف العمود request_id
            $table->dropColumn('request_id');

            // إعادة العمود exclusion_id
            $table->unsignedBigInteger('exclusion_id')->nullable()->after('employee_id');
            $table->foreign('exclusion_id')->references('id')->on('exclusions')->onDelete('cascade');
        });
    }
}
