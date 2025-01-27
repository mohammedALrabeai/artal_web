<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToExclusionsTable extends Migration
{
    public function up()
    {
        Schema::table('exclusions', function (Blueprint $table) {
            $table->string('status')->default('Pending')->after('notes'); // إضافة العمود مع القيمة الافتراضية
        });
    }

    public function down()
    {
        Schema::table('exclusions', function (Blueprint $table) {
            $table->dropColumn('status'); // حذف العمود في حالة التراجع
        });
    }
}
