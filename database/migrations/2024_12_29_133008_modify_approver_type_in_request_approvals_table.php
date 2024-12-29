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
        Schema::table('request_approvals', function (Blueprint $table) {
            $table->string('approver_type')->nullable()->change(); // جعل الحقل اختياريًا
        });
    }
    
    public function down(): void
    {
        Schema::table('request_approvals', function (Blueprint $table) {
            $table->string('approver_type')->nullable(false)->change(); // إعادة الحقل إلى الوضع الإجباري
        });
    }
    
};
