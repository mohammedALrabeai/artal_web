<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['current_approver_id']); // إزالة المفتاح الأجنبي
            $table->dropColumn('current_approver_id'); // حذف العمود القديم
            $table->string('current_approver_role')->nullable(); // إضافة العمود الجديد لتخزين الدور
        });
    }
    
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->unsignedBigInteger('current_approver_id')->nullable(); // إعادة العمود القديم
            $table->foreign('current_approver_id')->references('id')->on('users'); // إعادة المفتاح الأجنبي
            $table->dropColumn('current_approver_role'); // حذف العمود الجديد
        });
    }
    

};
