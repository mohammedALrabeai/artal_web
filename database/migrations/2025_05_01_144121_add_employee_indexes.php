<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index('national_id');
            $table->index('first_name');
            $table->index('family_name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['national_id']);
            $table->dropIndex(['first_name']);
            $table->dropIndex(['family_name']);
            $table->dropIndex(['status']);
        });
    }
};
