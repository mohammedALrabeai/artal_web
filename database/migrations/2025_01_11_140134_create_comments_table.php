<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('related_table'); // اسم الجدول المرتبط
            $table->unsignedBigInteger('related_id'); // معرف السجل المرتبط
            $table->text('comment'); // نص الملاحظة
            $table->timestamps(); // تاريخ الإنشاء والتحديث
        });
    }

    public function down()
    {
        Schema::dropIfExists('comments');
    }
}
