<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * تسجيل الحضور أو الانصراف للموظف.
     */
    public function markAttendance(Request $request)
    {
        // الحصول على الموظف من التوكن
        $employee = $request->user();

        // جلب تاريخ اليوم
        $date = Carbon::now()->toDateString();

        // تحديد وقت البداية المتوقع للحضور
        // $expectedStartTime = Carbon::createFromTime(9, 0, 0); // الساعة 9:00 صباحًا
            // قراءة الوقت المتوقع من الطلب
    $expectedStartTime = $request->input('expected_start_time'); // متغير الوقت المطلوب

    if (!$expectedStartTime) {
        return response()->json(['message' => 'Expected start time is required'], 400);
    }

    $expectedStartTime = Carbon::createFromFormat('H:i', $expectedStartTime);

        $currentTime = Carbon::now();

        // جلب سجل الحضور أو إنشاؤه إذا لم يكن موجودًا
        $attendance = Attendance::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $date,
            ],
            [
                'zone_id' => $request->zone_id,
                'check_in' => $currentTime->toTimeString(),
                'status' => 'present',
                'is_late' => $currentTime->gt($expectedStartTime), // تأخير إذا كان بعد 9:00 صباحًا
                'notes' => $request->notes ?? null, // الملاحظات من الطلب
            ]
        );

        if ($attendance->check_out) {
            return response()->json([
                'message' => 'You have already checked out for today.',
            ], 400);
        }

        // إذا كان الحضور مسجل بالفعل، يتم تسجيل الانصراف
        if ($attendance->check_in && !$attendance->check_out) {
            $attendance->update([
                'check_out' => $currentTime->toTimeString(),
            ]);

            // حساب ساعات العمل
            $workHours = Carbon::parse($attendance->check_in)->diffInHours(Carbon::parse($attendance->check_out));
            $attendance->update(['work_hours' => $workHours]);

            return response()->json([
                'message' => 'Checked out successfully.',
                'attendance' => $attendance,
            ]);
        }

        return response()->json([
            'message' => 'Checked in successfully.',
            'attendance' => $attendance,
        ]);
    }





    public function checkIn(Request $request)
{
    $employee = $request->user();
    $date = Carbon::now()->toDateString();
    $currentDateTime = Carbon::now();

    // تسجيل البيانات المطلوبة
    $attendance = Attendance::firstOrCreate(
        ['employee_id' => $employee->id, 'date' => $date],
        [
            'zone_id' => $request->zone_id,
            'shift_id' => $request->shift_id,
            'ismorning'=>$request->ismorning,
            'check_in' => Carbon::now()->toTimeString(),
            'check_in_datetime' => $currentDateTime,
            'status' => 'present',
            'is_late' => Carbon::now()->gt(Carbon::createFromFormat('H:i', $request->input('expected_start_time'))),
            'notes' => $request->input('notes'),
        ]
    );

    return response()->json([
        'message' => 'Checked in successfully.',
        'attendance' => $attendance,
    ]);
}

// public function checkOut(Request $request)
// {
//     $employee = $request->user();
//     $date = Carbon::now()->toDateString();

//     $currentDateTime = Carbon::now();

//     $attendance = Attendance::where('employee_id', $employee->id)->where('date', $date)->first();

//     if (!$attendance || !$attendance->check_in) {
//         return response()->json(['message' => 'Cannot check-out without check-in.'], 400);
//     }

//     if ($attendance->check_out) {
//         return response()->json(['message' => 'Already checked out.'], 400);
//     }

//     $currentTime = Carbon::now()->toTimeString();

//     // حساب ساعات العمل
//     $workHours = Carbon::parse($attendance->check_in)->diffInMinutes(Carbon::now()) / 60;

//     $attendance->update([
//         'check_out' => $currentTime,
//         'work_hours' => $workHours,
//         'notes' => $attendance->notes . ' | ' . $request->input('notes'),
//     ]);

//     return response()->json([
//         'message' => 'Checked out successfully.',
//         'attendance' => $attendance,
//     ]);
// }

public function checkOut(Request $request)
{
    $employee = $request->user();
    $currentDateTime = Carbon::now();

    // تحديد اليوم الحالي واليوم السابق
    $today = $currentDateTime->toDateString();
    $yesterday = $currentDateTime->copy()->subDay()->toDateString();

    // البحث عن الحضور لهذا اليوم أو اليوم السابق
    $attendance = Attendance::where('employee_id', $employee->id)
        ->where(function ($query) use ($today, $yesterday) {
            $query->where('date', $today)
                  ->orWhere('date', $yesterday);
        })
        ->whereNotNull('check_in_datetime') // التأكد من وجود وقت الحضور
        ->latest('check_in_datetime') // جلب آخر سجل حضور
        ->first();

    if (!$attendance) {
        return response()->json(['message' => 'Cannot check-out without check-in.'], 400);
    }

    if ($attendance->check_out || $attendance->check_out_datetime) {
        return response()->json(['message' => 'Already checked out.'], 400);
    }

    // حساب ساعات العمل بناءً على وقت الحضور
    $workHours = Carbon::parse($attendance->check_in_datetime)->diffInMinutes($currentDateTime) / 60;

    // تحديث بيانات الانصراف
    $attendance->update([
        'check_out' => $currentDateTime->toTimeString(), // العمود القديم
        'check_out_datetime' => $currentDateTime, // العمود الجديد
        'work_hours' => $workHours,
        'notes' => $attendance->notes . ' | ' . $request->input('notes'),
    ]);

    return response()->json([
        'message' => 'Checked out successfully.',
        'attendance' => $attendance,
    ]);
}



public function filter(Request $request)
{
    $user = $request->user();
    // التواريخ المطلوبة من الـ request
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    if (!$startDate || !$endDate) {
        return response()->json(['message' => 'start_date and end_date are required'], 400);
    }

    try {
             // تعديل التاريخين لضبط الوقت
             $startDateTime = $startDate . ' 00:00:00';
             $endDateTime = $endDate . ' 23:59:59';
            //  echo $startDateTime;
            //  echo $endDateTime;
        // استرجاع السجلات من قاعدة البيانات بناءً على الفترة الزمنية
        $attendances = Attendance::with('zone')->where('employee_id', $user->id)
        ->whereBetween('created_at', [$startDateTime, $endDateTime])
        ->get();
        return response()->json([
            'message' => 'Attendances retrieved successfully',
            'data' => $attendances
        ], 200);

    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to fetch records', 'error' => $e->getMessage()], 500);
    }
}

}
