<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBanksTable extends Migration
{
    public function up()
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم البنك
            $table->string('address')->nullable(); // عنوان البنك
            $table->string('contact_number')->nullable(); // رقم الاتصال
            $table->string('email')->nullable(); // البريد الإلكتروني
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('banks');
    }
}