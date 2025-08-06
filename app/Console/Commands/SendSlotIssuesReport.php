<?php 
// app/Console/Commands/SendSlotIssuesReport.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Mail, Storage};
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SlotIssuesReport;

class SendSlotIssuesReport extends Command
{
    protected $signature = 'report:send-slot-issues {emails?*}';
    protected $description = 'Generate slot-issues report and email it as one Excel file.';

    public function handle(): void
    {
        /* ------------- 1. تحديد المستلمين ------------- */
        $recipients = $this->argument('emails') ?: config('reports.slot_issues_recipients', []);
        if (empty($recipients)) {
            $this->error('❌ لا يوجد عناوين بريد لإرسال التقرير.');
            return;
        }

        /* ------------- 2. إنشاء وتخزين الملف ------------- */
       $fileName = 'slot-issues-' . now()->format('Ymd_His') . '.xlsx';
        $filePath = 'reports/' . $fileName;
        Excel::store(new SlotIssuesReport, $filePath, 'local');

        $fullPath = Storage::disk('local')->path($filePath);

        /* ------------- 3. تحضير رسالة البريد ------------- */
        $html = view('emails.slot-issues-report')->render();  //  أنشئ Blade صغير لو أردت تنسيقًا أفضل

        try {
            foreach ($recipients as $to) {
                Mail::send([], [], fn($m) => $m
                    ->to($to)
                    ->subject('🚦 تقرير أخطاء الشواغر')
                    ->html($html)
                    ->attach($fullPath)
                );
            }

            $this->info('✅ تم إرسال التقرير إلى: ' . implode(', ', $recipients));
        } catch (\Throwable $e) {
            $this->error('❌ فشل إرسال البريد: ' . $e->getMessage());
        }
    }
}


// # 1) استخدام العناوين المضبوطة في config/reports.php
// php artisan report:send-slot-issues

// # 2) تمرير العناوين يدويًا
// php artisan report:send-slot-issues hr@example.com ops@example.com ceo@example.com