<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsuranceCompaniesTable extends Migration
{
    public function up()
    {
        Schema::create('insurance_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم شركة التأمين
            $table->date('activation_date'); // تاريخ التفعيل
            $table->date('expiration_date'); // تاريخ الانتهاء
            $table->string('policy_number'); // رقم الوثيقة
            $table->string('branch'); // الفرع
            $table->timestamps(); // created_at و updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('insurance_companies');
    }
}
