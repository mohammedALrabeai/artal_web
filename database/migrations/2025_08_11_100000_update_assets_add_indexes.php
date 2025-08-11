<?php

// database/migrations/2025_08_11_100000_update_assets_add_indexes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'inventory_code')) {
                $table->string('inventory_code')->nullable()->after('serial_number');
                $table->unique('inventory_code', 'assets_inventory_code_unique');
            }

            // unique على الرقم التسلسلي (يسمح بعدة NULL تلقائياً في MySQL)
            $table->unique('serial_number', 'assets_serial_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropUnique('assets_serial_number_unique');
            if (Schema::hasColumn('assets', 'inventory_code')) {
                $table->dropUnique('assets_inventory_code_unique');
                $table->dropColumn('inventory_code');
            }
        });
    }
};
