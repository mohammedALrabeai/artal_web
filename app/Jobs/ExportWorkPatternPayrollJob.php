<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CombinedAttendanceWorkPatternExport;
use Carbon\Carbon;
use App\Services\NotificationService;
use App\Models\User;

class ExportWorkPatternPayrollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $projectIds;
    protected ?string $date;
    protected int $userId;
     public int $timeout = 600; 

    public function __construct(array $projectIds, ?string $date, int $userId)
    {
        $this->projectIds = $projectIds;
        $this->date = $date;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(NotificationService $notificationService)
    {
        $monthName = Carbon::parse($this->date)->translatedFormat('F_Y');
        
        // --- 1. تصدير التقرير المدمج والوحيد ---
        $export = new CombinedAttendanceWorkPatternExport($this->projectIds, $this->date);
        $fileName = "تقرير جدول التشغيل والرواتب - {$monthName}.xlsx";
        $filePath = 'public/exports/' . $fileName;

        // استخدم 'public' disk مباشرة
        Excel::store($export, 'exports/' . $fileName, 'public');
        
        \Log::info("تم إنشاء تقرير التشغيل المدمج: {$fileName}");

        // --- 2. إرسال إشعار "اكتمل التقرير" ---
        $user = User::find($this->userId);
        if ($user) {
            // تأكد من أن هذا المسار يعمل بشكل صحيح
            $downloadUrl = route('downloads.report', ['fileName' => $fileName]);

            $notificationService->sendNotification(
                 ['manager', 'general_manager', 'hr'], // أرسل الإشعار للمستخدم الذي طلب التقرير
                $user->name,
                "اكتمل إعداد تقرير \"{$fileName}\" وهو الآن جاهز للتحميل.",
                [
                    $notificationService->createAction('تحميل التقرير', $downloadUrl, 'heroicon-s-document-arrow-down'),
                ]
            );
        }
    }

    // ---==** تم حذف دالة createMissingShiftsCsv بالكامل **==---
}
