<?php

// app/Console/Commands/GenerateMonthlyAttendanceEmployeesCommand.php

namespace App\Console\Commands;

use App\Models\EmployeeProjectRecord;
use App\Models\ManualAttendanceEmployee;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyAttendanceEmployeesCommand extends Command
{
    protected $signature = 'attendance:generate-month {--month=}';
    protected $description = 'Generate manual_attendance_employees for the specified month (or current month if none given)';

    public function handle(): int
    {
        $month = $this->option('month')
            ? Carbon::parse($this->option('month'))->startOfMonth()
            : now('Asia/Riyadh')->startOfMonth();

        $monthStr = $month->toDateString();

        // ⚠️ نفحص إن كان هناك أي سجل لنفس الشهر (اختياري للإشعار فقط)
        $already = ManualAttendanceEmployee::where('attendance_month', $monthStr)->exists();
        if ($already) {
            $this->warn("⚠️ Some records already exist for {$month->format('F Y')}. I will upsert (no duplicates).");
        }

        // نحضر السجلات الفعالة وبها zone_id
        $query = EmployeeProjectRecord::query()
            ->where('status', true)
            ->where(function ($q) {
                $today = now('Asia/Riyadh')->toDateString();
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->whereNotNull('zone_id')
            ->select(['id', 'zone_id']);

        $total = 0;

        DB::transaction(function () use ($query, $monthStr, &$total) {
            $query->chunkById(1000, function ($records) use ($monthStr, &$total) {
                $nowTs = now('Asia/Riyadh');
                $rows = [];

                foreach ($records as $record) {
                    $rows[] = [
                        'employee_project_record_id' => $record->id,
                        'actual_zone_id'             => $record->zone_id,   // 🚩 من EPR
                        'attendance_month'           => $monthStr,
                        'is_main'                    => true,               // الافتراضي نعم
                        'created_at'                 => $nowTs,
                        'updated_at'                 => $nowTs,
                    ];
                }

                if (!empty($rows)) {
                    // upsert على الفهرس الفريد الثلاثي
                    ManualAttendanceEmployee::upsert(
                        $rows,
                        ['attendance_month', 'employee_project_record_id', 'actual_zone_id'],
                        ['updated_at'] // لا نعدل أعمدة أخرى عند وجوده
                    );
                    $total += count($rows);
                }
            });
        });

        $this->info("✅ Upserted seed for {$total} employee-project records into manual_attendance_employees for {$monthStr}.");
        return self::SUCCESS;
    }
}


// php artisan attendance:generate-month --month=2025-08-01 
