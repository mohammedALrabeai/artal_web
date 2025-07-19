<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Shift;
use App\Models\EmployeeProjectRecord;
use Carbon\Carbon;

class InitShiftShortageCount extends Command
{
    protected $signature = 'shift:init-shortage-count';
    protected $description = 'تهيئة عمود shortage_days_count للورديات ذات النقص استنادًا إلى تاريخ آخر انتهاء موظف';

    public function handle()
    {
        $today = today();
        $shifts = Shift::with('employeeProjectRecords')->get();

        $updated = 0;

        $this->info("🔍 بدء تحليل الورديات:");

        foreach ($shifts as $shift) {
            $assignedCount = $shift->employeeProjectRecords()
                ->where('status', 1)
                ->count();

            $required = $shift->emp_no;

            // عرض التفاصيل
            // $this->line("🔹 Shift #{$shift->id} - {$shift->name} | مطلوب: {$required} | مسند حالياً: {$assignedCount}");

            if ($assignedCount >= $required) {
                // $this->line("✅ لا يوجد نقص → تصفير العداد");
                $shift->updateQuietly(['shortage_days_count' => 0]);
                continue;
            }
                        $this->line("🔹 Shift #{$shift->id} - {$shift->name} | مطلوب: {$required} | مسند حالياً: {$assignedCount}");


            // فيه نقص → نبحث عن آخر end_date
            $lastEnded = EmployeeProjectRecord::where('shift_id', $shift->id)
                ->whereNotNull('end_date')
                ->orderByDesc('end_date')
                ->first();

            if ($lastEnded) {
                $endDate = Carbon::parse($lastEnded->end_date);
                $days = max(1, $endDate->diffInDays($today));

                $this->line("⚠️ يوجد نقص منذ {$endDate->toDateString()} → تم احتساب {$days} يوم");
            } else {
                $days = 1;
                $this->line("⚠️ لا يوجد end_date لأي موظف → تم ضبط العداد على 1");
            }

            $shift->updateQuietly(['shortage_days_count' => $days]);
            $updated++;
        }

        $this->info("🎯 تم تحديث {$updated} وردية تحتوي على نقص.");
        return Command::SUCCESS;
    }
}
