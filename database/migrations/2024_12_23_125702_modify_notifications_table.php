<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'channel')) {
                $table->string('channel')->nullable(); // إضافة عمود channel
            }

            if (!Schema::hasColumn('notifications', 'read_at')) {
                $table->timestamp('read_at')->nullable(); // إضافة عمود read_at
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('channel');
            $table->dropColumn('read_at');
        });
    }
};
