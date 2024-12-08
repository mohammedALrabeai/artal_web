<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLatLongInZonesTable extends Migration
{
    public function up()
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->decimal('lat', 20, 15)->change(); // تغيير الحقل إلى decimal مع عدد خانات أكبر
            $table->decimal('long', 20, 15)->change();
            $table->integer('area')->change(); 
        });
    }

    public function down()
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->decimal('lat', 8, 6)->change(); // الرجوع إلى التكوين السابق إذا لزم الأمر
            $table->decimal('long', 8, 6)->change();
            $table->string('area')->change();
        });
    }
}
