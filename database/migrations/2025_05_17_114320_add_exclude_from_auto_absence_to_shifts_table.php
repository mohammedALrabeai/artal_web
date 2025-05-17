<?php

// database/migrations/xxxx_xx_xx_add_exclude_from_auto_absence_to_shifts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExcludeFromAutoAbsenceToShiftsTable extends Migration
{
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('exclude_from_auto_absence')
                ->default(false)
                ->after('status')
                ->comment('إذا كانت true يتم استثناء موظفي هذه الوردية من الغياب التلقائي');
        });
    }

    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('exclude_from_auto_absence');
        });
    }
}
