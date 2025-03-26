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
    public function processAttendance()
    {
        Log::info('Starting processAttendance');
        try {
            // استرجاع الموظفين النشطين
            $activeEmployees = EmployeeProjectRecord::where('status', true)->get();
            Log::info('Active employees retrieved', ['count' => $activeEmployees->count()]);

            foreach ($activeEmployees as $record) {
                // Log::info('Processing employee', ['employee_id' => $record->employee_id]);

                // التأكد إذا كان اليوم يوم عمل
                if (! $record->isWorkingDay()) {
                    // Log::info('Not a working day', ['employee_id' => $record->employee_id]);

                    $this->markAttendance($record, 'off');

                    continue; // الانتقال إلى الموظف التالي
                }

                // استرجاع وقت الورديّة
                $shift = $record->shift;
                if (! $shift) {
                    Log::info('No shift found', ['employee_id' => $record->employee_id]);

                    continue; // إذا لم تكن هناك وردية، تجاوز الموظف
                }

                $now = Carbon::now('Asia/Riyadh'); // الوقت الحالي بتوقيت الرياض
                $today = Carbon::today('Asia/Riyadh'); // التاريخ الحالي بتوقيت الرياض

                // تحديد الفترة حسب الدورة الزمنية (صباحية أو مسائية)
                $shiftType = $this->getShiftTypeForToday($record);

                // تحديد مواعيد الوردية بناءً على نوع الفترة
                if ($shiftType === 'morning') {
                    $shiftStart = $today->copy()->setTimeFrom(Carbon::createFromTimeString($shift->morning_start));
                    $lastEntryTime = $shiftStart->copy()->addMinutes($shift->last_entry_time);
                } else {
                    $shiftStart = $today->copy()->setTimeFrom(Carbon::createFromTimeString($shift->evening_start));
                    $lastEntryTime = $shiftStart->copy()->addMinutes($shift->last_entry_time);

                    // إذا تجاوزت النهاية منتصف الليل
                    if ($lastEntryTime->lessThan($shiftStart)) {
                        $lastEntryTime->addDay();
                    }
                }

                // التحقق إذا تم تحضير الموظف مسبقاً
                $attendanceExists = Attendance::where('employee_id', $record->employee_id)
                    ->whereDate('date', $now->toDateString())
                    ->exists();

                if ($attendanceExists) {
                    // Log::info('Attendance already exists', ['employee_id' => $record->employee_id]);

                    continue; // إذا تم التحضير، تجاوز الموظف
                }

                // ✅ تسجيل الموظف كـ غائب إذا تجاوز الوقت الحالي وقت آخر دخول
                if ($now->greaterThan($lastEntryTime)) {
                    $this->markAttendance($record, 'absent');
                }
            }
            Log::info('processAttendance completed successfully');
        } catch (\Exception $e) {
            Log::error('Error in processAttendance', ['message' => $e->getMessage()]);
        }
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
    private function markAttendance(EmployeeProjectRecord $record, $status)
    {
        // التحقق من وجود سجل بالفعل لنفس الموظف في نفس اليوم والحالة
        $existingAttendance = Attendance::where('employee_id', $record->employee_id)
            ->whereDate('date', Carbon::today('Asia/Riyadh'))
            ->where('status', $status)
            ->exists();

        if ($existingAttendance) {
            // Log::info('Attendance already recorded', [
            //     'employee_id' => $record->employee_id,
            //     'status' => $status,
            // ]);

            return; // لا تقم بتسجيل سجل جديد
        }

        // ✅ إذا كان الموظف غائبًا، تحديث الحقل `out_of_zone` إلى `false`
        if ($status === 'absent') {
            $record->employee->update(['out_of_zone' => false]);
            // Log::info('Employee marked as not out_of_zone', [
            //     'employee_id' => $record->employee_id,
            // ]);
        }

        // إذا لم يكن هناك سجل موجود، قم بإنشاء سجل جديد
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

        Log::info('Attendance marked', [
            'employee_id' => $record->employee_id,
            'status' => $status,
        ]);
    }
}
