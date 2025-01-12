<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('employee_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id'); // معرف الموظف
            $table->string('type'); // نوع الإشعار
            $table->string('title'); // عنوان الإشعار
            $table->text('message'); // محتوى الإشعار
            $table->string('attachment')->nullable(); // مسار المرفق (اختياري)
            $table->boolean('sent_via_whatsapp')->default(false); // إرسال عبر واتساب
            $table->timestamps();

            // إعداد المفتاح الخارجي
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_notifications');
    }
}
