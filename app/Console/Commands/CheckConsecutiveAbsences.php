<?php

namespace App\Console\Commands;

use App\Exports\ConsecutiveAbsenceExport;
use App\Models\EmployeeStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CheckConsecutiveAbsences extends Command
{
    protected $signature = 'attendance:check-absences';

    protected $description = 'Generate report for employees with high consecutive absence count';

    public function handle()
    {
        $threshold = 4; // عدد الغيابات المتتالية المطلوبة للتقرير
        $exportData = collect();

        // ✅ جلب الموظفين الذين تجاوزوا العتبة
        $statuses = EmployeeStatus::with('employee.projectRecords.project', 'employee.projectRecords.zone', 'employee.projectRecords.shift')
            ->where('consecutive_absence_count', '>=', $threshold)
            ->get();

        foreach ($statuses as $status) {
            $employee = $status->employee;

            // جلب أحدث إسناد نشط للمشروع
            $record = $employee->projectRecords
                ->filter(fn ($r) => $r->status && (! $r->end_date || $r->end_date >= now()) && $r->start_date <= now())
                ->sortByDesc('start_date')
                ->first();

            $exportData->push([
                $employee->name,
                $employee->id,
                $employee->national_id,
                $employee->mobile_number,
                optional($record?->project)->name,
                optional($record?->zone)->name,
                optional($record?->shift)->name,
                $status->consecutive_absence_count,
                $status->last_present_at ? \Carbon\Carbon::parse($status->last_present_at)->toDateString() : '',

            ]);
        }

        if ($exportData->isNotEmpty()) {
            $fileName = 'consecutive_absences_'.now()->format('Y_m_d_His').'.xlsx';
            $filePath = 'exports/'.$fileName;

            Excel::store(new ConsecutiveAbsenceExport($exportData), $filePath, 'local');

            // ✅ قائمة الإيميلات (BCC)
            $emails = [
                'legal@artalgroup.net',
                'admin2@artalgroup.net',
                'sultan@artalgroup.net',
                'hradmin@artalgroup.net',
                'mohammedalrabeai@gmail.com',
                'legal2@artalgroup.net',
            ];
            $absentCount = $exportData->count();

            $htmlMessage = "
            <html dir='rtl' lang='ar'>
            <head>
                <meta charset='UTF-8'>
            </head>
            <body style='font-family: Tahoma, Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>
                <div style='background-color: #ffffff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>
                    <h2 style='color: #333;'>📄 تقرير الغياب المتتالي</h2>
                    <p style='font-size: 16px; color: #555;'>
                        نحيطكم علمًا بأن النظام قد رصد وجود <strong>{$absentCount} موظف</strong> لديهم غياب متتالي تجاوز الحد المسموح به (4 أيام أو أكثر).
                    </p>
            
                    <p style='font-size: 16px; color: #555;'>
                        تجدون في المرفق تقرير Excel يحتوي على التفاصيل الكاملة.
                    </p>
            
                    <p style='font-size: 16px; color: #555;'>
                        يرجى منكم اتخاذ الإجراء المناسب حسب سياسة الموارد البشرية.
                    </p>
            
                    <p style='margin-top: 30px; font-size: 14px; color: #888;'>
                        مع أطيب التحيات،<br>
                        Artal Solutions Team
                    </p>
                </div>
            </body>
            </html>";

            $fullPath = Storage::disk('local')->path($filePath); // 🔥 هذا يجلب المسار الفعلي الصحيح

            try {
                Mail::send([], [], function ($mail) use ($emails, $fullPath, $htmlMessage) {
                    $mail->to('mohammed.artalgroup@gmail.com')
                        ->bcc($emails)
                        ->subject('📄 تقرير الغياب المتتالي للموظفين')
                        ->html($htmlMessage)
                        ->attach($fullPath);
                });

            } catch (\Throwable $e) {
                \Log::error('فشل إرسال تقرير الغياب عبر البريد', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error('❌ فشل إرسال البريد، تم تسجيل الخطأ.');

                return;
            }

            $this->info('✅ تم إرسال تقرير الغياب المتتالي إلى القائمة المحددة.');
        } else {
            $this->info('✅ لا يوجد موظفون تجاوزوا الحد المطلوب للغياب المتتالي.');
        }
    }
}
