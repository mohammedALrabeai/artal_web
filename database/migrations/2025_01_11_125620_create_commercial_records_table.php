<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommercialRecordsTable extends Migration
{
    public function up()
    {
        Schema::create('commercial_records', function (Blueprint $table) {
            $table->id();
            $table->string('record_number')->unique(); // رقم السجل التجاري
            $table->string('entity_name'); // اسم المنشأة
            $table->string('city'); // المدينة
            $table->string('entity_type'); // الكيان
            $table->decimal('capital', 15, 2)->nullable(); // رأس المال
            $table->string('insurance_number')->nullable(); // رقم المنشأة بالتأمينات
            $table->string('labour_office_number')->nullable(); // رقم المنشأة في مكتب العمل
            $table->string('unified_number')->nullable(); // الرقم الموحد (700)
            $table->date('expiry_date_hijri')->nullable(); // نهاية السجل التجاري (هجري)
            $table->date('expiry_date_gregorian')->nullable(); // نهاية السجل التجاري (ميلادي)
            $table->string('tax_authority_number')->nullable(); // رقم المنشأة بالزكاة والضريبة والجمارك
            $table->integer('remaining_days')->nullable(); // متبقي بالأيام
            $table->string('vat')->nullable(); // رقم VAT
            $table->unsignedBigInteger('parent_company_id')->nullable(); // معرف الشركة الأم
            $table->foreign('parent_company_id')->references('id')->on('commercial_records')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('commercial_records');
    }
}
