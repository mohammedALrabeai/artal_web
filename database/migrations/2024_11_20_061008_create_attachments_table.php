<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade'); // ربط بالموظف
            $table->foreignId('added_by')->nullable()->constrained('users')->onDelete('set null'); // المستخدم الذي أضاف المرفق
            $table->enum('type', ['text', 'link', 'image', 'video', 'file'])->default('file'); // نوع الوثيقة
            $table->string('content'); // المحتوى (نص أو رابط)
            $table->date('expiry_date')->nullable(); // تاريخ انتهاء الوثيقة
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attachments');
    }
}
