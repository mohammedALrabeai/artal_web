<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExclusionsTable extends Migration
{
    public function up()
    {
        Schema::create('exclusions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // معرف الموظف المرتبط
            $table->string('type'); // نوع الاستبعاد
            $table->date('exclusion_date'); // تاريخ الاستبعاد
            $table->text('reason')->nullable(); // السبب
            $table->string('attachment')->nullable(); // المرفقات
            $table->text('notes')->nullable(); // الملاحظات
            $table->timestamps();

            // العلاقات
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('exclusions');
    }
}
