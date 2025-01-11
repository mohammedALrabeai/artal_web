<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToPrivateLicensesTable extends Migration
{
    public function up()
    {
        Schema::table('private_licenses', function (Blueprint $table) {
            $table->string('website')->nullable()->after('description'); // الموقع الإلكتروني
            $table->string('platform_username')->nullable()->after('website'); // اسم دخول المنصة
            $table->string('platform_password')->nullable()->after('platform_username'); // الرقم السري للمنصة
            $table->string('platform_user_id')->nullable()->after('platform_password'); // هوية مستخدم المنصة
            $table->date('expiry_date_hijri')->nullable()->after('expiry_date'); // نهاية الترخيص (هجري)
        });
    }

    public function down()
    {
        Schema::table('private_licenses', function (Blueprint $table) {
            $table->dropColumn([
                'website',
                'platform_username',
                'platform_password',
                'platform_user_id',
                'expiry_date_hijri',
            ]);
        });
    }
}
