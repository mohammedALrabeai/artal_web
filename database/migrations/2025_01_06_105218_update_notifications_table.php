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
        Schema::table('notifications', function (Blueprint $table) {
            // تغيير نوع العمود "data" إلى "jsonb"
            if (Schema::hasColumn('notifications', 'data')) {
                $table->jsonb('data')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // إعادة نوع العمود "data" إلى "text" إذا كنت بحاجة إلى التراجع
            if (Schema::hasColumn('notifications', 'data')) {
                $table->text('data')->change();
            }
        });
    }
};
