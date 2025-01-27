<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExclusionIdToAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('exclusion_id')->nullable()->after('employee_id'); // ربط مع الاستبعادات
            $table->foreign('exclusion_id')->references('id')->on('exclusions')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropForeign(['exclusion_id']);
            $table->dropColumn('exclusion_id');
        });
    }
}
