<?php
// database/migrations/2025_07_23_000002_create_manual_attendances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('manual_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_attendance_employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status'); // مثل: present, absent, M, N, OFF, leave, UV, W
            $table->boolean('has_coverage_shift')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['manual_attendance_employee_id', 'date'], 'unique_employee_day');
        });
    }

    public function down(): void {
        Schema::dropIfExists('manual_attendances');
    }
};
