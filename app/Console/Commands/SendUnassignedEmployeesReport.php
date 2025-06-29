<?php

namespace App\Console\Commands;

use App\Exports\UnassignedEmployeesExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class SendUnassignedEmployeesReport extends Command
{
    protected $signature = 'report:send-unassigned-employees {email}';
    protected $description = 'إرسال تقرير الموظفين غير المرتبطين بأي سلوت إلى البريد كملف Excel';

    public function handle(): int
    {
        $email = $this->argument('email');
        $now = now()->format('Ymd_His');
        $fileName = "unassigned-employees-{$now}.xlsx";
        $filePath = "reports/{$fileName}";

        // 1. توليد التقرير (إكسل)
        $export = new UnassignedEmployeesExport();

        // جلب البيانات (افترض أن الكلاس فيه دالة all())
        $data = $export->collection();
        if ($data->count() == 0) {
            $this->warn("لا يوجد موظفون غير مخصصين لأي سلوت.");
            return self::SUCCESS;
        }

        // تخزين الملف
        Excel::store($export, $filePath, 'local');

        // تأكد أن الملف موجود
        if (!Storage::exists($filePath)) {
            $this->error("❌ فشل حفظ ملف التقرير: $filePath");
            return self::FAILURE;
        }

        // 2. إرسال البريد
        try {
            Mail::raw('مرفق تقرير الموظفين غير المخصصين لأي سلوت، يرجى اتخاذ اللازم.', function ($message) use ($email, $filePath, $fileName) {
                $message->to($email)
                    ->subject('تقرير الموظفين غير المخصصين لأي سلوت')
                    ->attach(storage_path("app/{$filePath}"), [
                        'as' => $fileName,
                        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
            });

            $this->info("✅ تم إرسال التقرير إلى: $email");

            // حذف الملف بعد الإرسال (اختياري)
            Storage::delete($filePath);
        } catch (\Exception $e) {
            $this->error("حدث خطأ أثناء إرسال البريد: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}


// This command generates a report of unassigned employees and sends it via email as an Excel file.
// php artisan report:send-unassigned-employees mohammedalrabeai@gmail.com
