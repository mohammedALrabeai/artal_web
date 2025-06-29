<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('preferred_zone_name')
                ->nullable()
                // ->after('id') // أو ضع الحقل بعد أي عمود مناسب
                ->comment('اسم الموقع المرشح للموظف');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('preferred_zone_name');
        });
    }
};
