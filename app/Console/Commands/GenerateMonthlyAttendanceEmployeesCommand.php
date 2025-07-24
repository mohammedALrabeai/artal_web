<?php

// app/Console/Commands/GenerateMonthlyAttendanceEmployeesCommand.php

namespace App\Console\Commands;

use App\Models\EmployeeProjectRecord;
use App\Models\ManualAttendanceEmployee;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateMonthlyAttendanceEmployeesCommand extends Command
{
    protected $signature = 'attendance:generate-month {--month=}';

    protected $description = 'Generate manual_attendance_employees for the specified month (or current month if none given)';

    public function handle(): int
    {
        $month = $this->option('month')
            ? Carbon::parse($this->option('month'))->startOfMonth()
            : now()->startOfMonth();

        $exists = ManualAttendanceEmployee::where('attendance_month', $month)->exists();

        if ($exists) {
            $this->warn("Attendance employees already generated for {$month->format('F Y')}.");
            return self::SUCCESS;
        }

        $activeRecords = EmployeeProjectRecord::query()
            ->where('status', true)
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->get();

        $count = 0;

        foreach ($activeRecords as $record) {
            ManualAttendanceEmployee::create([
                'employee_project_record_id' => $record->id,
                'attendance_month' => $month->copy()->toDateString(),
            ]);
            $count++;
        }

        $this->info("✅ Generated $count manual attendance employees for {$month->format('F Y')}.");
        return self::SUCCESS;
    }
}



// php artisan attendance:generate-month --month=2025-07-01 