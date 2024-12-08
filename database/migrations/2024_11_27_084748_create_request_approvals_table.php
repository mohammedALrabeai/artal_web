<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->onDelete('cascade'); // الطلب المرتبط
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade'); // المستخدم الذي يوافق
            $table->string('approver_type'); // نوع المستخدم (مدير، مدير عام، إلخ)
            $table->string('status')->default('pending'); // حالة الموافقة
            $table->text('notes')->nullable(); // ملاحظات الموافقة
            $table->timestamp('approved_at')->nullable(); // تاريخ الموافقة
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_approvals');
    }
};
