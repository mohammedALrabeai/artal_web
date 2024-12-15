<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeCoordinatesWithDistance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_coordinates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');  // معرف الموظف
            $table->decimal('latitude', 18, 12);        // خط العرض بدقة عالية
            $table->decimal('longitude', 18, 12);       // خط الطول بدقة عالية
            $table->timestamp('timestamp');             // الوقت الذي تم فيه التتبع
            $table->enum('status', ['inside', 'outside'])->default('outside'); // حالة التواجد (داخل أو خارج الموقع)
            $table->unsignedBigInteger('shift_id')->nullable();     // معرف الوردية
            $table->unsignedBigInteger('zone_id')->nullable();      // معرف المنطقة
            $table->decimal('distance', 10, 2)->nullable();         // المسافة عن الموقع المستهدف (كم)
            $table->timestamps();

            // إضافة foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_coordinates');
    }
}
