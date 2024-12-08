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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->enum('type', ['مسائي', 'صباحي', 'مسائي صباحي', 'صباحي مسائي']);
            $table->time('morning_start')->nullable();
            $table->time('morning_end')->nullable();
            $table->time('evening_start')->nullable();
            $table->time('evening_end')->nullable();
            $table->time('early_entry_time')->nullable();
            $table->time('last_entry_time')->nullable();
            $table->time('early_exit_time')->nullable();
            $table->time('last_time_out')->nullable();
            $table->date('start_date');
            $table->integer('emp_no');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
