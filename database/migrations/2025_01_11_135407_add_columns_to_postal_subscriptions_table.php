<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToPostalSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::table('postal_subscriptions', function (Blueprint $table) {
            $table->string('mobile_number')->nullable()->after('notes'); // رقم الجوال
            $table->date('expiry_date_hijri')->nullable()->after('expiry_date'); // نهاية اشتراك الصندوق (هجري)
        });
    }

    public function down()
    {
        Schema::table('postal_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['mobile_number', 'expiry_date_hijri']);
        });
    }
}
