<?php

// app/Console/Commands/SendUnassignedEmployeesReport.php

namespace App\Console\Commands;

use App\Exports\UnassignedEmployeesExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SendUnassignedEmployeesReport extends Command
{
    protected $signature = 'report:send-unassigned-employees {email}';
    protected $description = 'Send report of employees without assigned slot to the given email as Excel attachment';

    public function handle()
    {
        $email = $this->argument('email');
        $fileName = 'unassigned-employees-' . now()->format('Ymd_His') . '.xlsx';
        $filePath = 'reports/' . $fileName;

        // 1. تخزين الملف
        Excel::store(new UnassignedEmployeesExport(), $filePath, 'local');

        // 2. جلب المسار الفعلي
        $fullPath = Storage::disk('local')->path($filePath);

        if (!file_exists($fullPath)) {
            $this->error("❌ الملف لم يتم إيجاده بعد الحفظ! تحقق من الصلاحيات أو الديسك.");
            return;
        }

        // 3. رسالة HTML للإيميل (بالعربي/إنجليزي حسب رغبتك)
        $htmlMessage = "
            <html dir='rtl' lang='ar'>
            <head>
                <meta charset='UTF-8'>
            </head>
            <body style='font-family: Tahoma, Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>
                <div style='background-color: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>
                    <h2 style='color: #333;'>📄 تقرير الموظفين غير المرتبطين بأي شاغر</h2>
                    <p style='font-size: 16px; color: #555;'>تجد في المرفق قائمة بالموظفين الذين لا يوجد لهم شاغر محدد في الورديات.</p>
                    <p style='font-size: 15px; color: #666;'>يرجى مراجعة الورديات واتخاذ الإجراء اللازم.</p>
                    <p style='margin-top: 30px; font-size: 14px; color: #888;'>
                        مع تحيات<br>
                        Artal Soft Team
                    </p>
                </div>
            </body>
            </html>";

        try {
            Mail::send([], [], function ($mail) use ($email, $fullPath, $htmlMessage) {
                $mail->to($email)
                    ->subject('📄 تقرير الموظفين غير المرتبطين بأي شاغر')
                    ->html($htmlMessage)
                    ->attach($fullPath);
            });

            $this->info('✅ تم إرسال التقرير بنجاح إلى: ' . $email);
            $this->info('مسار الملف: ' . $fullPath);

        } catch (\Throwable $e) {
            $this->error('❌ فشل إرسال البريد: ' . $e->getMessage());
        }
    }
}

//
// This command generates a report of unassigned employees and sends it via email as an Excel file.
// php artisan report:send-unassigned-employees mohammedalrabeai@gmail.com

