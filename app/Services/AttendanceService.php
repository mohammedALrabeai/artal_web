<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\EmployeeProjectRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    /**
     * إنشاء التحضير التلقائي
     */
    public function processAttendance(): array
    {
        Log::info('Starting processAttendance');

        $absentCount = 0;
        $offCount = 0;

        try {
            $activeEmployees = EmployeeProjectRecord::where('status', true)->whereNotNull('shift_id')
                ->whereHas(
                    'shift',
                    fn($q) => $q->where('exclude_from_auto_absence', false)
                )
                ->with(['shift.zone.pattern'])->get();
            Log::info('Active employees retrieved', ['count' => $activeEmployees->count()]);

            foreach ($activeEmployees as $record) {
                if (! $record->isWorkingDay()) {
                    if ($this->markAttendance($record, 'off')) {
                        $offCount++;
                    }

                    continue;
                }
                if (! $record->shift || ! $record->shift->zone || ! $record->shift->zone->pattern) {
                    Log::warning('❗Missing relation for employee', [
                        'employee_id' => $record->employee_id,
                        'has_shift' => $record->shift ? true : false,
                        'has_zone' => $record->shift?->zone ? true : false,
                        'has_pattern' => $record->shift?->zone?->pattern ? true : false,
                    ]);
                    // continue; // تخطي هذا الموظف لأنه غير مكتمل البيانات
                }

                $shift = $record->shift;
                if (! $shift) {
                    continue;
                }

                $now = Carbon::now('Asia/Riyadh');
                $today = Carbon::today('Asia/Riyadh');

                $shiftType = $this->getShiftTypeForToday($record);
                if ($shiftType === 'morning') {
                    $shiftStart = $today->copy()->setTimeFrom(Carbon::createFromTimeString($shift->morning_start));
                    $lastEntryTime = $shiftStart->copy()->addMinutes($shift->last_entry_time);
                } else {
                    $shiftStart = $today->copy()->setTimeFrom(Carbon::createFromTimeString($shift->evening_start));
                    $lastEntryTime = $shiftStart->copy()->addMinutes($shift->last_entry_time);
                    if ($lastEntryTime->lessThan($shiftStart)) {
                        $lastEntryTime->addDay();
                    }
                }

                $presentExists = Attendance::where('employee_id', $record->employee_id)
                    ->whereDate('date', $now->toDateString())
                    ->where('status', 'present')
                    ->exists();

                if ($presentExists) {
                    continue;
                }

                if ($now->greaterThan($lastEntryTime)) {
                    if ($this->markAttendance($record, 'absent')) {
                        $absentCount++;
                    }
                }
            }

            Log::info('processAttendance completed successfully');
        } catch (\Exception $e) {
            Log::error('Error in processAttendance', ['message' => $e->getMessage()]);
        }

        return [
            'absent' => $absentCount,
            'off' => $offCount,
        ];
    }

    private function getShiftTypeForToday($record)
    {
        $pattern = $record->shift->zone->pattern;
        $cycleLength = $pattern->working_days + $pattern->off_days;

        $startDate = Carbon::parse($record->shift->start_date);
        $daysSinceStart = $startDate->diffInDays(Carbon::today('Asia/Riyadh'));
        $cycleNumber = floor($daysSinceStart / $cycleLength) + 1;

        $shiftType = $record->shift->type;

        if ($shiftType === 'morning_evening') {
            return ($cycleNumber % 2 == 1) ? 'morning' : 'evening';
        } elseif ($shiftType === 'evening_morning') {
            return ($cycleNumber % 2 == 1) ? 'evening' : 'morning';
        }

        return $shiftType;
    }

    /**
     * تسجيل الحضور أو الغياب
     */
    private function markAttendance(EmployeeProjectRecord $record, $status): bool
    {
        $today = Carbon::today('Asia/Riyadh');

        // رموز الإجازات من قاعدة البيانات
        $leaveCodes = \App\Models\LeaveType::pluck('code')->toArray();

        // الحالات التي نمنع معها التسجيل (تحضير فعلي أو إجازة أو نفس الحالة المُرسلة)
        $excludedStatuses = array_merge(['present'], $leaveCodes, [$status]);

        $alreadyMarked = Attendance::where('employee_id', $record->employee_id)
            ->whereDate('date', $today)
            ->whereIn('status', $excludedStatuses)
            ->exists();

        if ($alreadyMarked) {
            return false; // تم تحضيره أو تسجيـل الحالة مسبقًا
        }


        if ($status === 'absent') {
            $record->employee->update(['out_of_zone' => false]);
        }

        Attendance::create([
            'employee_id' => $record->employee_id,
            'zone_id' => $record->zone_id,
            'shift_id' => $record->shift_id,
            'date' => Carbon::today('Asia/Riyadh'),
            'status' => $status,
            'check_in' => null,
            'check_out' => null,
            'notes' => $status === 'off' ? 'Day off' : ($status === 'absent' ? 'Absent' : null),
        ]);

        if ($status === 'absent') {
            $employeeStatus = \App\Models\EmployeeStatus::firstOrNew([
                'employee_id' => $record->employee_id,
            ]);

            $employeeStatus->consecutive_absence_count = ($employeeStatus->consecutive_absence_count ?? 0) + 1;
            $employeeStatus->save();
        }

        Log::info('Attendance marked', [
            'employee_id' => $record->employee_id,
            'status' => $status,
        ]);

        return true; // تم تسجيل السجل
    }

    /**
     * إرجاع الموظفين الغائبين فعليًا في تاريخ معين.
     */
    public function getTrulyAbsentEmployees(string $date): Collection
    {
        // 1. الموظفون الذين لديهم سجل غياب اليوم
        $absentIds = Attendance::whereDate('date', $date)
            ->where('status', 'absent')
            ->pluck('employee_id')
            ->unique();

        // 2. الموظفون الذين لديهم أي سجل آخر (حضور أو تغطية) اليوم
        $notAbsent = Attendance::whereDate('date', $date)
            ->where(function ($query) {
                $query->where('status', '!=', 'absent')
                    ->orWhere('is_coverage', true);
            })
            ->pluck('employee_id')
            ->unique();

        // 3. استثناء الموظفين الذين لديهم سجل آخر
        $finalAbsentIds = $absentIds->diff($notAbsent);

        // 4. جلب التفاصيل
        return Attendance::with(['employee', 'zone', 'shift'])
            ->whereDate('date', $date)
            ->where('status', 'absent')
            ->whereIn('employee_id', $finalAbsentIds)
            ->get();
    }
}
