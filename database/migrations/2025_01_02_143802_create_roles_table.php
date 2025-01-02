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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // اسم الدور
            $table->integer('level')->unsigned()->unique(); // مستوى الدور (1، 2، 3...)
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(); // مفتاح أجنبي للأدوار
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
            $table->dropColumn('role'); // حذف العمود القديم
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
            $table->enum('role', ['manager', 'general_manager', 'hr']); // إعادة العمود القديم
        });

        Schema::dropIfExists('roles');
    }
};
