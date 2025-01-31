<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->integer('level')->default(1); // مستوى الدور، كلما كان أكبر زادت الصلاحيات
            $table->integer('priority')->default(1); // أهمية الدور، يمكن استخدامها في الموافقات
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('level');
            $table->dropColumn('priority');
        });
    }
};
