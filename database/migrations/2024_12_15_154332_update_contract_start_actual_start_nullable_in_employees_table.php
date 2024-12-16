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
            // تعديل الأعمدة لتصبح nullable
            $table->date('contract_start')->nullable()->change();
            $table->date('actual_start')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // إعادة الأعمدة إلى حالتها الأصلية (غير nullable)
            $table->date('contract_start')->nullable(false)->change();
            $table->date('actual_start')->nullable(false)->change();
        });
    }
};
