<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coverages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade'); // الموظف الذي يقوم بالتغطية
            $table->foreignId('absent_employee_id')->constrained('employees')->onDelete('cascade'); // الموظف الغائب
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->foreignId('added_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coverages');
    }
};
