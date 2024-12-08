<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoansTable extends Migration
{
    public function up()
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade'); // معرف الموظف
            $table->foreignId('bank_id')->constrained('banks')->onDelete('cascade'); // معرف البنك
            $table->decimal('amount', 15, 2); // مبلغ القرض
            $table->integer('duration_months'); // مدة القرض بالأشهر
            $table->string('purpose'); // الغرض من القرض
            $table->date('start_date')->nullable(); // تاريخ بداية القرض
            $table->date('end_date')->nullable(); // تاريخ انتهاء القرض
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('loans');
    }
}