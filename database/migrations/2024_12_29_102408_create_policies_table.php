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
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_name'); // اسم السياسة
            $table->string('policy_type'); // نوع السياسة (إجازة، سلفة، إلخ)
            $table->text('description')->nullable(); // وصف السياسة
            $table->text('conditions'); // شروط السياسة
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
