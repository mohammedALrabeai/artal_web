<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZoneStatusAndNotificationToEmployeeStatusesTable extends Migration
{
    public function up()
    {
        Schema::table('employee_statuses', function (Blueprint $table) {
            $table->boolean('is_inside')
                ->default(false)
                ->comment('Indicates if the employee is inside the zone (true = inside, false = outside)');
            $table->boolean('notification_enabled')
                ->default(true)
                ->comment('Determines if dashboard notifications are enabled');
        });
    }

    public function down()
    {
        Schema::table('employee_statuses', function (Blueprint $table) {
            $table->dropColumn(['is_inside', 'notification_enabled']);
        });
    }
}
