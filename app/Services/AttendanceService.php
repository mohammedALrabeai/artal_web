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
                Log::info('Processing employee', ['employee_id' => $record->employee_id]);

                // التأكد إذا كان اليوم يوم عمل
                if (!$record->isWorkingDay()) {
                    Log::info('Not a working day', ['employee_id' => $record->employee_id]);

                    $this->markAttendance($record, 'off');
                    continue; // الانتقال إلى الموظف التالي
                }

                // استرجاع وقت الورديّة
                $shift = $record->shift;
                if (!$shift) {
                    Log::info('No shift found', ['employee_id' => $record->employee_id]);
                    continue; // إذا لم تكن هناك وردية، تجاوز الموظف
                }


                $now = Carbon::now('Asia/Riyadh'); // الوقت الحالي بتوقيت الرياض
                $today = Carbon::today('Asia/Riyadh'); // التاريخ الحالي بتوقيت الرياض

                // وقت بداية الصباح مع التاريخ الحالي
                $morningStart = $today->copy()->setTimeFrom(Carbon::createFromTimeString($shift->morning_start));
                
                // حساب وقت الدخول المبكر
                $earlyEntryTime = $morningStart->copy()->subMinutes($shift->early_entry_time);
                
                // حساب وقت آخر دخول
                $lastEntryTime = $morningStart->copy()->addMinutes($shift->last_entry_time);
                
                // التحقق من النتائج
                dd(   [
                    'morning_start' => $morningStart,
                    'early_entry_time' => $earlyEntryTime,
                    'last_entry_time' => $lastEntryTime,
                    'current_time' => Carbon::now('Asia/Riyadh'),
                ]);
                // التحقق إذا تم تحضير الموظف مسبقاً
                $attendanceExists = Attendance::where('employee_id', $record->employee_id)
                    ->whereDate('date', $now->toDateString())
                    ->exists();

                if ($attendanceExists) {
                    Log::info('Attendance already exists', ['employee_id' => $record->employee_id]);
                    continue; // إذا تم التحضير، تجاوز الموظف
                }
              // التحقق من حالة الحضور بناءً على الوقت
            if ($now->between($earlyEntryTime, $lastEntryTime)) {
                Log::info('Within shift time', ['employee_id' => $record->employee_id]);
                // $this->markAttendance($record, 'present'); // تسجيل الحضور
            } elseif ($now->greaterThan($lastEntryTime)) {
                Log::info('Shift time exceeded', ['employee_id' => $record->employee_id]);
                $this->markAttendance($record, 'absent'); // تسجيل الغياب
            }
            }
            Log::info('processAttendance completed successfully');
        } catch (\Exception $e) {
            Log::error('Error in processAttendance', ['message' => $e->getMessage()]);
        }
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
            Log::info('Attendance already recorded', [
                'employee_id' => $record->employee_id,
                'status' => $status,
            ]);
            return; // لا تقم بتسجيل سجل جديد
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
