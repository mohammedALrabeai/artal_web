<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEmployeesTableNullableFields extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('street')->nullable()->change();
            $table->string('building_number')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('street')->nullable(false)->change();
            $table->string('building_number')->nullable(false)->change();
        });
    }
}
