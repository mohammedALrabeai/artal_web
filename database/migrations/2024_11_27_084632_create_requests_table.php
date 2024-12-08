<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // نوع الطلب
            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade'); // المستخدم الذي قدّم الطلب
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('cascade'); // الموظف المرتبط بالطلب
            $table->foreignId('current_approver_id')->nullable()->constrained('users')->onDelete('set null'); // المستخدم الذي يوافق حاليًا
            $table->string('status')->default('pending'); // حالة الطلب
            $table->text('description')->nullable(); // وصف الطلب
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
