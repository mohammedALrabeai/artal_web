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
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('leave_balance')->default(0); // حدد العمود السابق
        });
    }
    
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('leave_balance');
        });
    }
    
};
