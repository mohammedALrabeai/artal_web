<?php

namespace App\Jobs;

use App\Models\Shift;
use App\Models\EmployeeProjectRecord;
use App\Models\ShiftShortageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateShiftShortageLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = today();
        $totalShifts = 0;
        $updatedLogs = 0;

        try {
            // تحميل جميع الورديات النشطة المرتبطة بمشاريع ومواقع نشطة
            $shifts = Shift::where('status', 1)
                ->with('zone.project')
                ->get();

            $totalShifts = $shifts->count();

            foreach ($shifts as $shift) {
                // حساب عدد الموظفين المسندين حاليًا
                $assignedCount = EmployeeProjectRecord::where('shift_id', $shift->id)
                    ->where('status', 1)
                    ->whereDate('start_date', '<=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                    })
                    ->count();

                $isShortage = $assignedCount < $shift->emp_no;

                // تسجيل السطر اليومي في جدول النقص
                ShiftShortageLog::updateOrCreate(
                    ['shift_id' => $shift->id, 'date' => $today],
                    [
                        'is_shortage' => $isShortage,
                        'notes' => "Assigned: {$assignedCount}, Required: {$shift->emp_no}",
                    ]
                );

                // تحديث العداد فقط إذا استمر النقص
                $shift->updateQuietly([
                    'shortage_days_count' => $isShortage
                        ? $shift->shortage_days_count + 1
                        : 0,
                ]);

                $updatedLogs++;
            }

            \Log::info("✅ [UpdateShiftShortageLogs] للل تم تحديث {$updatedLogs} وردية من أصل {$totalShifts} في " . now());
        } catch (\Throwable $e) {
            \Log::error("❌ خطأ أثناء تنفيذ UpdateShiftShortageLogs: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            throw $e;
        }
    }
}
