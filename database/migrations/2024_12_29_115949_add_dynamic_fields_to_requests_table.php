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
        Schema::table('requests', function (Blueprint $table) {
            $table->integer('duration')->nullable(); // مدة الطلب (مثلاً، عدد أيام الإجازة)
            $table->decimal('amount', 10, 2)->nullable(); // المبلغ المطلوب (مثلاً للسلف)
            $table->json('additional_data')->nullable(); // بيانات إضافية تعتمد على النوع
            $table->boolean('required_documents')->default(false);
            $table->string('target_location')->nullable();

        });
    }
    
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['duration', 'amount', 'additional_data']);
            $table->dropColumn('required_documents');
            $table->dropColumn('target_location');

        });
    }
    
};
