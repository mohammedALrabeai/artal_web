<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLongAndAreaInZonesTable extends Migration
{
    public function up()
    {
        Schema::table('zones', function (Blueprint $table) {
            // تغيير اسم العمود من long إلى longg
            $table->renameColumn('long', 'longg');

            // تغيير نوع العمود area إلى integer
            $table->integer('area')->change();
        });
    }

    public function down()
    {
        Schema::table('zones', function (Blueprint $table) {
            // إعادة تسمية العمود إلى long
            $table->renameColumn('longg', 'long');

            // إعادة نوع العمود area إلى string
            $table->string('area')->change();
        });
    }
}
