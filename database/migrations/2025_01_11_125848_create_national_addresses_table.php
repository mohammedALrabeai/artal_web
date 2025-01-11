<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNationalAddressesTable extends Migration
{
    public function up()
    {
        Schema::create('national_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commercial_record_id'); // مرتبط بالسجل التجاري
            $table->date('expiry_date')->nullable(); // نهاية العنوان الوطني
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamps();

            $table->foreign('commercial_record_id')->references('id')->on('commercial_records')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('national_addresses');
    }
}
