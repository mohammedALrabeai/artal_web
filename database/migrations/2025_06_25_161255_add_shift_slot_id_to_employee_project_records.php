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
        Schema::table('employee_project_records', function (Blueprint $table) {
    $table->foreignId('shift_slot_id')->nullable()->constrained('shift_slots')->nullOnDelete();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_project_records', function (Blueprint $table) {
            //
        });
    }
};
