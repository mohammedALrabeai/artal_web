<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDatetimeColumnsToAttendancesTable extends Migration
{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dateTime('check_in_datetime')->nullable()->after('check_in');
            $table->dateTime('check_out_datetime')->nullable()->after('check_out');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['check_in_datetime', 'check_out_datetime']);
        });
    }
}
