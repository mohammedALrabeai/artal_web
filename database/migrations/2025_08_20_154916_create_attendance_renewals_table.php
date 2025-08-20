<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_renewals', function (Blueprint $table) {
            $table->id();

            // ربط التجديد بسجل الحضور الأساسي
            $table->foreignId('attendance_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // وقت التجديد من السيرفر
            $table->timestamp('renewed_at')->useCurrent()->index();

            // نوع التجديد وحالته (سلاسل قصيرة قابلة للتغيير لاحقًا لـ ENUM)
            $table->string('kind', 20)->default('manual'); // manual | auto | voice
            $table->string('status', 20)->default('ok');   // ok | canceled | expired

            // مجال حر لأي بيانات مستقبلية
            $table->json('payload')->nullable();

            $table->timestamps();

            // تسريع جلب آخر تجديد لكل Attendance
            $table->index(['attendance_id', 'renewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_renewals');
    }
};
