<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMunicipalLicensesTable extends Migration
{
    public function up()
    {
        Schema::create('municipal_licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commercial_record_id'); // مرتبط بالسجل التجاري
            $table->string('license_number')->unique(); // رقم رخصة البلدية
            $table->date('expiry_date_hijri')->nullable(); // نهاية رخصة البلدية (هجري)
            $table->date('expiry_date_gregorian')->nullable(); // نهاية رخصة البلدية (ميلادي)
            $table->string('vat')->nullable(); // VAT
            $table->timestamps();

            $table->foreign('commercial_record_id')->references('id')->on('commercial_records')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('municipal_licenses');
    }
}
