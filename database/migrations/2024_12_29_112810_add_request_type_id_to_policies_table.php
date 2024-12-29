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
        Schema::table('policies', function (Blueprint $table) {
            if (Schema::hasColumn('policies', 'request_type_id')) {
                $table->dropColumn('request_type_id');
            }
            $table->unsignedBigInteger('request_type_id')->nullable()->after('policy_type');
    
            // إضافة علاقة إلى جدول الأنواع
            $table->foreign('request_type_id')->references('id')->on('request_types')->onDelete('cascade');
        });
    }
    
    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropForeign(['request_type_id']);
            $table->dropColumn('request_type_id');
        });
    }
    
};
