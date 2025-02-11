<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->enum('approval_status', ['submitted', 'pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->change(); // ✅ تغيير العمود بدلاً من إضافته
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->change(); // ✅ التراجع إلى الحالة الأصلية عند التراجع عن الميجريشن
        });
    }
};
