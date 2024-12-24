<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resignations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade'); // ربط بالموظف
            $table->date('resignation_date'); // تاريخ الاستقالة
            $table->string('reason')->nullable(); // سبب الاستقالة
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resignations');
    }
};
