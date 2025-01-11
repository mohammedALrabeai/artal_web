<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInsuranceAndJobColumnsToEmployeesTable extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            // التحقق من وجود العمود قبل إضافته
            if (!Schema::hasColumn('employees', 'insurance_company_id')) {
                $table->unsignedBigInteger('insurance_company_id')->nullable(); // شركة التأمين
            }

            if (!Schema::hasColumn('employees', 'job_title')) {
                $table->string('job_title')->nullable(); // المسمى الوظيفي
            }

            if (!Schema::hasColumn('employees', 'bank_name')) {
                $table->string('bank_name')->nullable(); // اسم البنك
            }

            if (!Schema::hasColumn('employees', 'insurance_type')) {
                $table->string('insurance_type')->nullable(); // نوع التأمين
            }

            if (!Schema::hasColumn('employees', 'insurance_number')) {
                $table->string('insurance_number')->nullable(); // رقم التأمين
            }

            if (!Schema::hasColumn('employees', 'insurance_start_date')) {
                $table->date('insurance_start_date')->nullable(); // تاريخ البداية
            }

            if (!Schema::hasColumn('employees', 'insurance_end_date')) {
                $table->date('insurance_end_date')->nullable(); // تاريخ النهاية
            }
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'job_title',
                'bank_name',
                'insurance_type',
                'insurance_company_id',
                'insurance_number',
                'insurance_start_date',
                'insurance_end_date',
            ]);
        });
    }
}
