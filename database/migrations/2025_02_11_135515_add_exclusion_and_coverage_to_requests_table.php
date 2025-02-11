<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->foreignId('exclusion_id')->nullable()->constrained('exclusions')->nullOnDelete();
            $table->foreignId('coverage_id')->nullable()->constrained('coverages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['exclusion_id']);
            $table->dropForeign(['coverage_id']);
            $table->dropColumn(['exclusion_id', 'coverage_id']);
        });
    }
};
