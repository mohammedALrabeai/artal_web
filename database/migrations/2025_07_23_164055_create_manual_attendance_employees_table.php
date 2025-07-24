<?php
// database/migrations/2025_07_23_000001_create_manual_attendance_employees_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('manual_attendance_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_project_record_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_month'); // أول يوم في الشهر
            $table->timestamps();

            $table->unique(['employee_project_record_id', 'attendance_month'], 'unique_employee_month');
        });
    }

    public function down(): void {
        Schema::dropIfExists('manual_attendance_employees');
    }
};
