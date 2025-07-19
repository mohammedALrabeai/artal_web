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
        Schema::create('shift_shortage_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
    $table->date('date');
    $table->boolean('is_shortage'); // true = يوجد نقص
    $table->text('notes')->nullable(); // ملاحظات اختيارية
    $table->timestamps();

    $table->unique(['shift_id', 'date']); // لا نريد تكرار السجلات لليوم نفسه
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_shortage_logs');
    }
};
