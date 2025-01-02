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
            $table->dropColumn('approver_type'); // حذف العمود approver_type
            $table->string('approver_role')->nullable(); // إضافة العمود approver_role
        });
    }
    
    public function down(): void
    {
        Schema::table('request_approvals', function (Blueprint $table) {
            $table->string('approver_type')->nullable(); // إعادة العمود approver_type
            $table->dropColumn('approver_role'); // حذف العمود approver_role
        });
    }
    
};
