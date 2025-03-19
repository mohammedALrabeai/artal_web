<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeStatusesTable extends Migration
{
    public function up()
    {
        Schema::create('employee_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('معرّف الموظف المرتبط بالحالة');
            $table->timestamp('last_seen_at')
                ->nullable()
                ->comment('آخر وقت تم فيه استقبال heartbeat');
            $table->boolean('gps_enabled')
                ->default(false)
                ->comment('يشير إلى ما إذا كان GPS مفعل');
            $table->timestamp('last_gps_status_at')
                ->nullable()
                ->comment('آخر وقت تم فيه تغيير حالة GPS');
            $table->json('last_location')
                ->nullable()
                ->comment('آخر إحداثيات الموقع (مثلاً: { "latitude": "XX.XXXX", "longitude": "YY.YYYY" })');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_statuses');
    }
}
