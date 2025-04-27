<?php

namespace App\Console\Commands;

use App\Exports\ConsecutiveAbsenceExport;
use App\Mail\ConsecutiveAbsenceReportMail;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class CheckConsecutiveAbsences extends Command
{
    protected $signature = 'attendance:check-absences';

    protected $description = 'Check employees with pure consecutive absences ignoring OFF days';

    public function handle()
    {
        $targetWorkDays = 3; // عدد أيام العمل المطلوبة
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();
        $exportData = collect();

        // تحميل الموظفين النشطين مع مشروع فعلي
        $employees = Employee::where('status', true)
        
            ->whereHas('projectRecords', function ($q) use ($today) {
                $q->where('status', 1)
                    ->where(function ($query) use ($today) {
                        $query->whereNull('end_date')->orWhere('end_date', '>=', $today);
                    })
                    ->where('start_date', '<=', $today);
            })
            ->with(['projectRecords.zone.pattern', 'projectRecords.project', 'projectRecords.zone', 'projectRecords.shift'])
            ->get();

        // تحميل الحضور دفعة واحدة
        $attendanceData = Attendance::whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('date', '<=', $yesterday)
            ->whereDate('date', '>=', $yesterday->copy()->subDays(10)) // تغطية نطاق زمني مناسب
            ->get()
            ->groupBy('employee_id')
            ->map(function ($attendances) {
                return $attendances->keyBy(function ($att) {
                    return Carbon::parse($att->date)->toDateString();
                });
            });

        foreach ($employees as $employee) {
            $record = $employee->projectRecords
                ->where('status', 1)
                ->filter(fn ($r) => (! $r->end_date || $r->end_date >= $today) && $r->start_date <= $today)
                ->sortByDesc('start_date')
                ->first();

            if (! $record || ! $record->zone || ! $record->zone->pattern) {
                continue;
            }

            $pattern = $record->zone->pattern;
            $workDays = collect();
            $checkDate = $yesterday->copy();

            while ($workDays->count() < $targetWorkDays) {
                $dayIndex = $checkDate->dayOfWeekIso;
                $isWorkingDay = $pattern->{'day_'.$dayIndex} ?? false;

                if ($isWorkingDay) {
                    $workDays->push($checkDate->copy());
                }
                $checkDate->subDay();
            }

            $sequence = [];
            foreach ($workDays as $workDay) {
                $dateString = $workDay->toDateString();
                $attendance = $attendanceData[$employee->id][$dateString] ?? null;

                if ($attendance) {
                    // حاضر أو تغطية
                    $sequence[] = 'P';
                } else {
                    // غائب
                    $sequence[] = 'A';
                }
            }

            // الآن نحكم على السلسلة
            if (! in_array('P', $sequence)) {
                $exportData->push([
                    $employee->full_name,
                    $employee->id,
                    $employee->national_id,
                    $employee->mobile_number,
                    optional($record->project)->name,
                    optional($record->zone)->name,
                    optional($record->shift)->name,
                    $targetWorkDays,
                    $workDays->first()->toDateString(),
                ]);
            }
        }

        // تصدير إلى ملف Excel
        if ($exportData->isNotEmpty()) {
            $fileName = 'consecutive_absences_'.now()->format('Y_m_d_His').'.xlsx';
            $filePath = 'exports/'.$fileName;
            Excel::store(new ConsecutiveAbsenceExport($exportData), $filePath, 'local');

            $emails = [
                'mohammedalrabeai@gmail.com',
            ];

            foreach ($emails as $email) {
                Mail::to($email)->queue(new ConsecutiveAbsenceReportMail($filePath));
            }

            $this->info('✅ تم إرسال تقرير الغياب المتتالي.');
        } else {
            $this->info('✅ لا يوجد موظفون متغيبون متتاليون.');
        }
    }
}
