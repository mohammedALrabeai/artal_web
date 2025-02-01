<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('coverages', function (Blueprint $table) {
            $table->enum('status', ['completed', 'cancelled', 'pending'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('coverages', function (Blueprint $table) {
            $table->enum('status', ['completed', 'cancelled'])->default('completed')->change();
        });
    }
};
