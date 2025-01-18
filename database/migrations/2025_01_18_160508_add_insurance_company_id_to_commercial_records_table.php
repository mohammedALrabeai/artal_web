<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('commercial_records', function (Blueprint $table) {
            $table->foreignId('insurance_company_id')->nullable()->constrained('insurance_companies')->onDelete('set null');
        });
    }
    
    public function down()
    {
        Schema::table('commercial_records', function (Blueprint $table) {
            $table->dropForeign(['insurance_company_id']);
            $table->dropColumn('insurance_company_id');
        });
    }
    
};
