<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\EmployeeProjectRecord;
use Carbon\Carbon;
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
        $existingAttendance = Attendance::where('employee_id', $record->employee_id)
            ->whereDate('date', Carbon::today('Asia/Riyadh'))
            ->where('status', $status)
            ->exists();

        if ($existingAttendance) {
            return false; // لم يتم تسجيل شيء جديد
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
}
