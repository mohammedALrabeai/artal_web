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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->foreignId('pattern_id')->constrained('patterns')->onDelete('cascade');
            $table->decimal('lat', 10, 7);
            $table->decimal('long', 10, 7);
            $table->string('area');
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
        Schema::dropIfExists('zones');
    }
};
