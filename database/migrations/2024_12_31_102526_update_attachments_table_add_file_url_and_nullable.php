<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAttachmentsTableAddFileUrlAndNullable extends Migration
{
    public function up()
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->string('file_url')->nullable()->after('content'); // إضافة الحقل الجديد
            $table->string('content')->nullable()->change(); // تعديل الحقل ليكون nullable
        });
    }

    public function down()
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('file_url'); // حذف الحقل الجديد عند التراجع
            $table->string('content')->nullable(false)->change(); // إعادة الحقل إلى الوضع الأصلي
        });
    }
}
