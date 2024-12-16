<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSlidesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slides', function (Blueprint $table) {
            $table->id(); // مفتاح أساسي
            $table->string('title')->nullable(); // عنوان السلايد
            $table->text('description')->nullable(); // وصف السلايد
            $table->string('image_url'); // رابط الصورة
            $table->boolean('is_active')->default(true); // حالة السلايد (نشط أم لا)
            $table->timestamps(); // وقت الإنشاء والتحديث
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('slides');
    }
}
