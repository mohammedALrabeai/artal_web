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
       Schema::create('employee_face_events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['enroll', 'verify'])->index();
    $table->string('disk')->default('public');
    $table->string('path'); // مثال: employees/123/face/verify/20250820_101010.jpg
    $table->timestamp('captured_at')->index();
    $table->decimal('similarity', 5, 2)->nullable();
    $table->string('rek_face_id')->nullable();
    $table->string('rek_image_id')->nullable();
    $table->json('meta')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_face_events');
    }
};
