<?php

namespace App\Console\Commands;

use App\Exports\UnassignedEmployeesExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class SendUnassignedEmployeesReport extends Command
{
    protected $signature = 'report:send-unassigned-employees {email}';
    protected $description = 'إرسال تقرير الموظفين غير المخصصين لسلوت إلى الإيميل كملف Excel';

    public function handle()
    {
        $email = $this->argument('email');
        $fileName = 'unassigned-employees-'.now()->format('Ymd_His').'.xlsx';
        $filePath = 'reports/' . $fileName;

        // 1. توليد ملف Excel وحفظه في التخزين المؤقت
        Excel::store(new UnassignedEmployeesExport, $filePath, 'local');

        // 2. إرسال البريد الإلكتروني مع المرفق
        Mail::raw('يرجى مراجعة الملف المرفق: الموظفون المعلقون بدون سلوت.', function ($message) use ($email, $filePath) {
            $message->to($email)
                ->subject('تقرير الموظفين غير المخصصين لسلوت')
                ->attach(storage_path('app/' . $filePath));
        });

        // 3. حذف الملف بعد الإرسال (اختياري)
        Storage::delete($filePath);

        $this->info("✅ تم إرسال التقرير إلى $email بنجاح.");
        return self::SUCCESS;
    }
}
