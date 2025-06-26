<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shift_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('slot_number'); // رقم المكان داخل الوردية

            $table->timestamps();

            $table->unique(['shift_id', 'slot_number']); // ضمان عدم تكرار نفس الرقم لنفس الوردية
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_slots');
    }
};
