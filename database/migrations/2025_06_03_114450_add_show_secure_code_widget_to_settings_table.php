<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_show_secure_code_widget_to_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShowSecureCodeWidgetToSettingsTable extends Migration
{
    public function up(): void
    {
        // إذا كانت الإعدادات مخزنة كسطر لكل مفتاح
        \DB::table('settings')->updateOrInsert(
            ['key' => 'show_secure_code_widget'],
            ['value' => false]
        );
    }

    public function down(): void
    {
        \DB::table('settings')->where('key', 'show_secure_code_widget')->delete();
    }
}
