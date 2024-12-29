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
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->string('request_type'); // نوع الطلب
            $table->integer('approval_level'); // مستوى الموافقة
            $table->string('approver_role'); // دور الشخص المسؤول عن الموافقة
            $table->text('conditions')->nullable(); // شروط الموافقة (اختياري)
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_flows');
    }
};
