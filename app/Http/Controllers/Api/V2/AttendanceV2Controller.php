<?php

namespace App\Http\Controllers\Api\V2;

use Carbon\Carbon;
use App\Models\Zone;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\EmployeeStatus;
use App\Http\Controllers\Controller;

class AttendanceV2Controller extends Controller
{
public function checkIn(Request $request)
{
    $request->validate([
        'code' => 'required|string|size:5',
        'employee_id' => 'required|exists:employees,id',
    ]);

    $employee = Employee::with('attendances')->findOrFail($request->employee_id);

    // فك الكود للحصول على zone_id
    try {
        $zoneId = app(\App\Services\CodeDecoder::class)->decode($request->input('code'), $employee->id);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'كود الموقع غير صالح: ' . $e->getMessage(),
        ], 422);
    }

    $now = now('Asia/Riyadh');
    $today = $now->toDateString();

    // التحقق من وجود سجل نشط لم يتم تسجيل انصرافه
    $activeToday = Attendance::where('employee_id', $employee->id)
        ->whereDate('date', $today)
        ->whereNull('check_out')
        ->latest('check_in_datetime')
        ->first();

    if ($activeToday) {
        $checkInTime = \Carbon\Carbon::parse($activeToday->check_in_datetime);
        $hoursPassed = $checkInTime->diffInHours($now);

        // ✅ نسمح فقط بالتحضير إذا السجل النشط حالته off أو مضى أكثر من 15 ساعة
        if ($activeToday->status !== 'off' && $hoursPassed <= 15) {
            return response()->json([
                'success' => false,
                'message' => 'لديك سجل تحضير مفتوح اليوم. يرجى تسجيل الانصراف أولاً.',
            ], 400);
        }
    }

    // تحديد نوع التحضير بناءً على وجود سجل سابق اليوم
    $status = 'present';

    $anyTodayRecord = Attendance::where('employee_id', $employee->id)
        ->whereDate('date', $today)
        ->where('status', '!=', 'off')
        ->first();

    if ($anyTodayRecord) {
        // إذا كان السجل السابق اليوم حضوري عادي، نعتبر الجديد تغطية
        if ($anyTodayRecord->status !== 'coverage') {
            $status = 'coverage';
        }
    }

    $attendance = Attendance::create([
        'employee_id' => $employee->id,
        'zone_id' => $zoneId,
        'date' => $today,
        'check_in' => $now->toTimeString(),
        'check_in_datetime' => $now,
        'status' => $status,
        'is_coverage' => $status === 'coverage',
        'is_late' => false,
    ]);

    if ($status === 'present') {
        $this->updateEmployeeStatusOnCheckIn($employee);
    }

    return response()->json([
        'success' => true,
        'message' => $status === 'coverage' ? 'تم تسجيل تغطية بنجاح' : 'تم تسجيل الحضور بنجاح',
        'data' => [
            'attendance_id' => $attendance->id,
            'status' => $attendance->status,
            'zone_id' => $zoneId,
            'check_in' => $attendance->check_in,
        ],
    ]);
}



    protected function updateEmployeeStatusOnCheckIn(Employee $employee): void
    {
        $status = EmployeeStatus::firstOrNew(['employee_id' => $employee->id]);

        $status->last_present_at = now('Asia/Riyadh')->toDateString();
        $status->consecutive_absence_count = 0;

        $status->save();
    }


  public function checkOut(Request $request)
{
    $request->validate([
        'code' => 'required|string|size:5',
        'employee_id' => 'required|exists:employees,id',
    ]);

    $employee = Employee::with('attendances')->findOrFail($request->employee_id);

    // فك الكود للحصول على zone_id
    try {
        $zoneId = app(\App\Services\CodeDecoder::class)->decode(
            $request->input('code'),
            $employee->id
        );
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'كود الموقع غير صالح: ' . $e->getMessage(),
        ], 422);
    }

    $now = now('Asia/Riyadh');

    // جلب السجل النشط (بدون check_out) في نفس الموقع
    $attendance = Attendance::where('employee_id', $employee->id)
        ->where('zone_id', $zoneId)
        ->whereNull('check_out')
        ->latest('check_in_datetime')
        ->first();

    if (! $attendance) {
        return response()->json([
            'success' => false,
            'message' => 'لا يوجد سجل تحضير نشط يمكن تسجيل الانصراف منه.',
        ], 400);
    }

    // ✅ تحويل check_in_datetime إلى كائن Carbon
    $checkInTime = Carbon::parse($attendance->check_in_datetime);

    // تحقق من الفارق الزمني — 15 ساعة
    $hoursPassed = $checkInTime->diffInHours($now);
    if ($hoursPassed > 15) {
        return response()->json([
            'success' => false,
            'message' => 'تجاوز وقت السجل 15 ساعة، لا يمكن تسجيل الانصراف. يرجى تسجيل حضور جديد.',
        ], 400);
    }

    // حساب ساعات العمل
    $workHours = $checkInTime->diffInMinutes($now) / 60;

    $attendance->update([
        'check_out' => $now->toTimeString(),
        'check_out_datetime' => $now,
        'work_hours' => $workHours,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'تم تسجيل الانصراف بنجاح',
        'data' => [
            'attendance_id' => $attendance->id,
            'status' => $attendance->status,
            'check_out' => $attendance->check_out,
            'work_hours' => round($workHours, 2),
        ],
    ]);
}

}
