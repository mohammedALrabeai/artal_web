<?php

namespace App\Console\Commands;

use App\Exports\UnassignedEmployeesExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Mail\ReportMail;

class SendUnassignedEmployeesReport extends Command
{
    protected $signature = 'report:send-unassigned-employees {email}';
    protected $description = 'يرسل تقرير الموظفين غير المسندين إلى الإيميل المحدد';

   public function handle()
{
    $export = new UnassignedEmployeesExport();
    $fileName = 'unassigned-employees-' . now()->format('Ymd_His') . '.xlsx';
    $filePath = 'reports/' . $fileName;

    // تأكد من وجود المجلد
    \Storage::makeDirectory('reports');

    // حفظ ملف الإكسل في ديسك local
    \Maatwebsite\Excel\Facades\Excel::store($export, $filePath, 'local');

    $fullPath = storage_path('app/' . $filePath);

    // اطبع المسار وتحقق منه
    $this->info("مسار الملف: $fullPath");
    if (!file_exists($fullPath)) {
        $this->error("❌ الملف لم يتم إيجاده بعد الحفظ! تحقق من الصلاحيات أو الديسك.");
        return 1;
    } else {
        $this->info("✅ الملف تم إنشاؤه بنجاح.");
    }

    // أرسل الإيميل مع إرفاق الملف بالمسار المطلق
    \Mail::to($this->argument('email'))->send(new \App\Mail\ReportMail($fullPath));

    $this->info('✅ تم إرسال التقرير بنجاح إلى ' . $this->argument('email'));
    return 0;
}

}


// This command generates a report of unassigned employees and sends it via email as an Excel file.
// php artisan report:send-unassigned-employees mohammedalrabeai@gmail.com
