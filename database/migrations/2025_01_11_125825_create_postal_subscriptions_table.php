<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostalSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('postal_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commercial_record_id'); // مرتبط بالسجل التجاري
            $table->string('subscription_number')->unique(); // رقم الاشتراك
            $table->date('start_date')->nullable(); // تاريخ البداية
            $table->date('expiry_date')->nullable(); // تاريخ الانتهاء
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamps();

            $table->foreign('commercial_record_id')->references('id')->on('commercial_records')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('postal_subscriptions');
    }
}
