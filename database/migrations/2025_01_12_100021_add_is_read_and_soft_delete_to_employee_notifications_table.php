<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsReadAndSoftDeleteToEmployeeNotificationsTable extends Migration
{
    public function up()
    {
        Schema::table('employee_notifications', function (Blueprint $table) {
            $table->boolean('is_read')->default(false); // حالة القراءة
            $table->softDeletes(); // الحذف الناعم
        });
    }

    public function down()
    {
        Schema::table('employee_notifications', function (Blueprint $table) {
            $table->dropColumn('is_read');
            $table->dropSoftDeletes();
        });
    }
}
