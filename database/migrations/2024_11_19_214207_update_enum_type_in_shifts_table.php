<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEnumTypeInShiftsTable extends Migration
{
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->enum('type', ['evening', 'morning', 'evening_morning', 'morning_evening'])->change();
        });
    }

    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->enum('type', ['مسائي', 'صباحي', 'مسائي صباحي', 'صباحي مسائي'])->change();
        });
    }
}
