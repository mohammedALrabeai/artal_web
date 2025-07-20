<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->timestamp('last_unattended_started_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn('last_unattended_started_at');
        });
    }
};
