<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrivateLicensesTable extends Migration
{
    public function up()
    {
        Schema::create('private_licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commercial_record_id'); // مرتبط بالسجل التجاري
            $table->string('license_name'); // اسم الترخيص
            $table->string('license_number')->unique(); // رقم الترخيص
            $table->date('issue_date')->nullable(); // تاريخ الإصدار
            $table->date('expiry_date')->nullable(); // تاريخ الانتهاء
            $table->text('description')->nullable(); // وصف الترخيص
            $table->timestamps();

            $table->foreign('commercial_record_id')->references('id')->on('commercial_records')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('private_licenses');
    }
}
