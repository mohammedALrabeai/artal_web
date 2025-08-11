<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'status')) {
                $table->string('status')->default('available')->after('condition');
            } else {
                $table->string('status')->default('available')->change();
            }
            // ملاحظة: نترك التحقق على مستوى التطبيق عبر Enum
        });
    }

    public function down(): void
    {
        // لا نعيد الحالة كما كانت لتفادي كسر البيانات؛ تعديل اختياري
    }
};
