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
        Storage::makeDirectory('reports');

        // حفظ ملف الإكسل
        Excel::store($export, $filePath, 'local');

        // تحقق من وجود الملف فعلاً قبل الإرسال
        if (!Storage::disk('local')->exists($filePath)) {
            $this->error("❌ فشل حفظ ملف التقرير: $filePath");
            return 1;
        }

        // إرسال الإيميل مع الملف كمرفق
        Mail::to($this->argument('email'))->send(new ReportMail($filePath));

        $this->info('✅ تم إرسال التقرير بنجاح إلى ' . $this->argument('email'));
        return 0;
    }
}


// This command generates a report of unassigned employees and sends it via email as an Excel file.
// php artisan report:send-unassigned-employees mohammedalrabeai@gmail.com
