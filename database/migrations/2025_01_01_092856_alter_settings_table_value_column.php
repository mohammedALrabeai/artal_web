<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterSettingsTableValueColumn extends Migration
{
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('value')->change(); // تأكد من أن القيمة يمكن أن تخزن JSON
        });
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('value')->change(); // إعادة التغيير إذا لزم الأمر
        });
    }
}
