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
        Schema::table('attachments', function (Blueprint $table) {
            $table->text('content')->nullable()->change(); // تغيير الحقل content ليصبح nullable للنصوص والروابط
            $table->string('image_url')->nullable(); // عمود خاص بالصور
            $table->string('video_url')->nullable(); // عمود خاص بالفيديوهات
            $table->string('file_url')->nullable()->change(); // عمود الملفات الحالي
        });
    }
    
    public function down()
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn(['image_url', 'video_url']);
            $table->string('content')->nullable(false)->change(); // إعادة الحقل content ليكون غير nullable
        });
    }
    
};
