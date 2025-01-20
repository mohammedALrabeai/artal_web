<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBloodTypeNullableInEmployeesTable extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('blood_type')->nullable()->change(); // جعل الحقل nullable
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('blood_type')->nullable(false)->change(); // استرجاع الحالة الأصلية (غير nullable)
        });
    }
}
