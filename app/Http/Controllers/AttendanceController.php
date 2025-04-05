<?php

namespace App\Http\Controllers;

use App\Events\NewNotification;
use App\Models\Attendance;
use App\Models\EmployeeProjectRecord;
use App\Models\User;
use App\Models\Zone;
use App\Notifications\CoverageRequestNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

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

        if (! $expectedStartTime) {
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
        if ($attendance->check_in && ! $attendance->check_out) {
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
        $date = Carbon::now('Asia/Riyadh')->toDateString();
        $currentDateTime = Carbon::now('Asia/Riyadh');

        // التحقق إذا كان الموظف قد سجل حضور طبيعي اليوم
        $existingAttendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $date)
            ->where('status', 'present')
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'message' => 'You have already checked in today.',
                'attendance' => $existingAttendance,
            ], 200);
        }

        // البحث عن أي سجل حضور سابق لهذا اليوم
        $existingAttendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        // البحث عن تغطية نشطة (لم يتم تسجيل انصراف لها)
        $activeCoverage = Attendance::where('employee_id', $employee->id)
            ->where('date', $date)
            ->where('status', 'coverage')
            ->whereNull('check_out') // لم يتم تسجيل انصراف
            ->exists();

        // إذا كان هناك سجل حضور سابق
        if ($existingAttendance) {
            // إذا كان الحضور السابق هو تغطية ولم يتم تسجيل انصراف، لا يمكن تسجيل دخول جديد
            if ($existingAttendance->status === 'coverage' && $activeCoverage) {
                return response()->json([
                    'message' => 'You are currently under a coverage session. Please check out first before checking in again.',
                    'attendance' => $existingAttendance,
                ], 400);
            }

            // التحقق من حالة الحضور السابقة
            switch ($existingAttendance->status) {
                case 'present':
                    return response()->json([
                        'message' => 'You have already checked in today.',
                        'attendance' => $existingAttendance,
                    ], 400);

                case 'off':
                    return response()->json([
                        'message' => 'You are off today. No need to check in.',
                        'attendance' => $existingAttendance,
                    ], 400);

                case 'M':
                    return response()->json([
                        'message' => 'You are on a Morbid leave today. No need to check in.',
                        'attendance' => $existingAttendance,
                    ], 400);

                case 'absent':
                    return response()->json([
                        'message' => 'You have been marked as absent today. Please contact your supervisor if this is incorrect.',
                        'attendance' => $existingAttendance,
                    ], 400);

                case 'leave':
                    return response()->json([
                        'message' => 'You are on a paid leave today. No need to check in.',
                        'attendance' => $existingAttendance,
                    ], 400);

                case 'UV':
                    return response()->json([
                        'message' => 'You are on an unpaid leave today. No need to check in.',
                        'attendance' => $existingAttendance,
                    ], 400);
            }
        }

        // تسجيل حضور طبيعي جديد
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'zone_id' => $request->zone_id,
            'shift_id' => $request->shift_id,
            'ismorning' => $request->ismorning,
            'date' => $date,
            'check_in' => Carbon::now('Asia/Riyadh')->toTimeString(),
            'check_in_datetime' => $currentDateTime,
            'status' => 'present',
            'is_late' => Carbon::now('Asia/Riyadh')->gt(Carbon::createFromFormat('H:i', $request->input('expected_start_time'))),
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'message' => 'Checked in successfully.',
            'attendance' => $attendance,
        ]);
    }

    public function checkInCoverage(Request $request)
    {
        $employee = $request->user(); // الموظف الحالي
        $date = Carbon::now('Asia/Riyadh')->toDateString(); // تاريخ اليوم
        $currentDateTime = Carbon::now('Asia/Riyadh'); // الوقت الحالي

        // التحقق من البيانات المطلوبة
        $request->validate([
            'zone_id' => 'required|exists:zones,id',
            // 'shift_id' => 'required|exists:shifts,id',
            'notes' => 'nullable|string',
        ]);

        // البحث عن آخر تغطية اليوم للموظف نفسه والتي لم يتم تسجيل الانصراف لها
        $existingCoverage = Attendance::where('employee_id', $employee->id)
            ->where('date', $date)
            ->where('status', 'coverage') // سجل تغطية
            ->whereNull('check_out') // لم يتم تسجيل انصراف
            ->first();

        if ($existingCoverage) {
            return response()->json([
                'message' => 'You cannot create a new coverage without checking out from the previous coverage.',
                'attendance' => $existingCoverage,
            ], 400);
        }

        // تسجيل تغطية جديدة
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'zone_id' => $request->zone_id,
            // 'shift_id' => $request->shift_id,
            'date' => $date,
            'check_in' => Carbon::now('Asia/Riyadh')->toTimeString(),
            'check_in_datetime' => $currentDateTime,
            'status' => 'coverage',
            'is_coverage' => true,
            'notes' => $request->input('notes'),
        ]);

        // جلب اسم الموظف بالكامل باستخدام دالة name()
        $employeeName = $employee->name();

        // جلب اسم المنطقة بناءً على معرف المنطقة (zone_id)
        $zone = Zone::find($request->zone_id);
        $zoneName = $zone ? $zone->name : 'غير معروف'; // إذا لم يتم العثور على المنطقة، يعرض "غير معروف"

        $notificationService = new NotificationService;
        $notificationService->sendNotification(
            ['manager', 'general_manager', 'hr'], // الأدوار المستهدفة
            'تسجيل تغطية جديدة', // عنوان الإشعار
            "قام الموظف {$employeeName} بتسجيل تغطية جديدة في منطقة {$zoneName}.", // نص الإشعار
            [
                // $notificationService->createAction('عرض تفاصيل التغطية', "/admin/coverages/{$attendance->id}/view", 'heroicon-s-eye'),
                $notificationService->createAction('عرض سجل الحضور', '/admin/attendances', 'heroicon-s-calendar'),
            ]
        );
        $managers = User::all();
        // إرسال الإشعار لجميع المستخدمين باستثناء المدراء والموارد البشرية
        // $users = User::whereNotIn('role', ['manager', 'general_manager', 'hr'])->get();

        Notification::send($managers, new CoverageRequestNotification($attendance));

        // 🔹 إنشاء بيانات الإشعار وإرسالها لحظيًا عبر `NewNotification`
        $notificationData = [
            'id' => (string) \Str::uuid(),
            'type' => 'App\\Notifications\\CoverageRequestNotification',
            'title' => 'طلب تغطية جديد',
            'message' => "📢 **طلب تغطية جديد**\n"
                ."👤 **الموظف:** {$employee->first_name} {$employee->father_name} {$employee->family_name} "
                ."(ID: {$employee->id})\n"
                ."📅 **التاريخ:** {$attendance->date}\n"
                .'⏰ **الحضور:** '.($attendance->check_in ?? 'غير متوفر')."\n"
                .'🏁 **الانصراف:** '.($attendance->check_out ?? 'غير متوفر')."\n"
                .'📍 **الموقع:** '.($attendance->zone->name ?? 'غير محدد')."\n"
                .'📝 **السبب:** '.($attendance->notes ?? 'لا يوجد سبب محدد')."\n"
                .'🔄 **الحالة:** '.($attendance->approval_status ?? 'في انتظار الموافقة')."\n"
                .'🔄 **هل هي تغطية؟** '.($attendance->is_coverage ? 'نعم' : 'لا')."\n"
                .'🚨 **خارج المنطقة؟** '.($attendance->out_of_zone ? 'نعم' : 'لا'),
            'attendance_id' => $attendance->id,
            'employee_id' => $attendance->employee->id,
            'employee_name' => "{$attendance->employee->first_name} {$attendance->employee->father_name} {$attendance->employee->family_name}",
            'date' => $attendance->date,
            'check_in' => $attendance->check_in ?? 'غير متوفر',
            'check_out' => $attendance->check_out ?? 'غير متوفر',
            'zone' => $attendance->zone->name ?? 'غير محدد',
            'reason' => $attendance->notes ?? 'لا يوجد سبب محدد',
            'status' => $attendance->approval_status ?? 'في انتظار الموافقة',
            'is_coverage' => $attendance->is_coverage ? 'نعم' : 'لا',
            'out_of_zone' => $attendance->out_of_zone ? 'نعم' : 'لا',
            'created_at' => now()->toDateTimeString(),
            'read_at' => null,
        ];

        // 🔹 إرسال الإشعار عبر `Pusher` للجميع مرة واحدة فقط
        event(new NewNotification($notificationData));

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
        $currentDateTime = Carbon::now('Asia/Riyadh');

        // إذا تم استلام المتغير main_attendance_id، استرجع السجل بناءً عليه
        if ($request->has('main_attendance_id')) {
            $attendance = Attendance::where('id', $request->input('main_attendance_id'))
                ->where('employee_id', $employee->id)
                ->first();

            if (! $attendance) {
                return response()->json(['message' => 'Attendance record not found.'], 400);
            }
        } else {
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

            if (! $attendance) {
                return response()->json(['message' => 'Cannot check-out without check-in.'], 400);
            }
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
            'notes' => $attendance->notes.' | '.$request->input('notes'),
        ]);

        return response()->json([
            'message' => 'Checked out successfully.',
            'attendance' => $attendance,
        ]);
    }

    public function checkOutCoverage(Request $request)
    {
        $employee = $request->user();
        $currentDateTime = Carbon::now('Asia/Riyadh');

        // تحديد اليوم الحالي واليوم السابق
        $today = $currentDateTime->toDateString();
        $yesterday = $currentDateTime->copy()->subDay()->toDateString();

        // البحث عن آخر سجل تغطية نشط (بدون انصراف)
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('status', 'coverage') // التأكد من حالة التغطية
            ->where(function ($query) use ($today, $yesterday) {
                $query->where('date', $today)
                    ->orWhere('date', $yesterday);
            })
            ->whereNotNull('check_in_datetime') // التأكد من وجود وقت الحضور
            ->whereNull('check_out_datetime') // لم يتم تسجيل انصراف
            ->latest('check_in_datetime') // جلب آخر تغطية مسجلة
            ->first();

        // التحقق من وجود سجل التغطية
        if (! $attendance) {
            return response()->json(['message' => 'No active coverage found to check-out.'], 400);
        }

        // التحقق من وجود انصراف سابق
        if ($attendance->check_out || $attendance->check_out_datetime) {
            return response()->json(['message' => 'Already checked out for this coverage.'], 400);
        }

        // حساب ساعات العمل بناءً على وقت الحضور
        $workHours = Carbon::parse($attendance->check_in_datetime)->diffInMinutes($currentDateTime) / 60;

        // تحديث بيانات الانصراف للتغطية
        $attendance->update([
            'check_out' => $currentDateTime->toTimeString(),
            'check_out_datetime' => $currentDateTime,
            'work_hours' => $workHours,
            'notes' => $attendance->notes.' | '.$request->input('notes'),
        ]);

        return response()->json([
            'message' => 'Checked out successfully for coverage.',
            'attendance' => $attendance,
        ]);
    }

    public function syncCheckIn(Request $request)
    {
        $employee = $request->user();
        // $date = Carbon::now('Asia/Riyadh')->toDateString();
        // $currentDateTime = Carbon::now('Asia/Riyadh');
        $currentDateTime = Carbon::parse($request->check_in_time);
        $date = $currentDateTime->toDateString();

        // تسجيل البيانات المطلوبة
        $attendance = Attendance::firstOrCreate(
            ['employee_id' => $employee->id, 'date' => $date],
            [
                'zone_id' => $request->zone_id,
                'shift_id' => $request->shift_id,
                'ismorning' => $request->ismorning,
                'check_in' => $currentDateTime->toTimeString(),
                'check_in_datetime' => $currentDateTime,
                'status' => 'present',
                'is_late' => $currentDateTime->gt(Carbon::createFromFormat('H:i', $request->input('expected_start_time'))),
                'notes' => $request->input('notes'),
            ]
        );

        return response()->json([
            'message' => 'Checked in successfully.',
            'attendance' => $attendance,
        ]);
    }

    public function syncCheckOut(Request $request)
    {
        $employee = $request->user();
        // $currentDateTime = Carbon::now('Asia/Riyadh');
        $currentDateTime = Carbon::parse($request->check_out_time);

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

        if (! $attendance) {
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
            'notes' => $attendance->notes.' | '.$request->input('notes'),
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

        if (! $startDate || ! $endDate) {
            return response()->json(['message' => 'start_date and end_date are required'], 400);
        }

        try {
            // تعديل التاريخين لضبط الوقت
            $startDateTime = $startDate.' 00:00:00';
            $endDateTime = $endDate.' 23:59:59';
            //  echo $startDateTime;
            //  echo $endDateTime;
            // استرجاع السجلات من قاعدة البيانات بناءً على الفترة الزمنية
            $attendances = Attendance::with('zone')->where('employee_id', $user->id)
                ->where('approval_status', '!=', 'rejected')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->get();

            return response()->json([
                'message' => 'Attendances retrieved successfully',
                'data' => $attendances,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch records', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAttendanceStatus(Request $request)
    {
        // التحقق من المدخلات
        $request->validate([
            'project_id' => 'required|integer',
            'zone_id' => 'required|integer',
            'shift_id' => 'sometimes|nullable|integer', // السماح بعدم وجوده
            'date' => 'required|date',
        ]);

        try {
            // جلب البيانات من المدخلات
            $projectId = $request->input('project_id');
            $zoneId = $request->input('zone_id');
            $shiftId = $request->input('shift_id', null); // استخدم null افتراضيًا إذا لم يتم تمريره

            $date = Carbon::parse($request->input('date'));

            // إذا كان shift_id موجودًا، جلب الموظفين الأساسيين
            $regularEmployees = collect(); // تهيئة قائمة الموظفين الأساسيين

            if ($shiftId) {
                $employeeRecords = EmployeeProjectRecord::with('employee')
                    ->where('project_id', $projectId)
                    ->where('zone_id', $zoneId)
                    ->where('shift_id', $shiftId)
                    ->where(function ($query) use ($date) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', $date);
                    })
                    ->where('start_date', '<=', $date)
                    ->get();

                $employees = $employeeRecords->map(function ($record) {
                    return $record->employee;
                });

                $attendanceRecords = Attendance::where('zone_id', $zoneId)
                    ->where('shift_id', $shiftId)
                    ->whereDate('date', $date)
                    ->get();

                $regularEmployees = $employees->map(function ($employee) use ($attendanceRecords) {
                    $attendance = $attendanceRecords->firstWhere('employee_id', $employee->id);

                    return [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->first_name.' '.$employee->father_name.' '.$employee->family_name,
                        'status' => $attendance ? $attendance->status : 'absent',
                        'check_in' => $attendance ? $attendance->check_in : null,
                        'check_out' => $attendance ? $attendance->check_out : null,
                        'mobile_number' => $employee->mobile_number,
                        'phone_number' => $employee->phone_number,
                        'notes' => $attendance ? $attendance->notes : null,
                        'is_coverage' => false,
                        'out_of_zone' => $employee ? $employee->out_of_zone : false,
                    ];
                });
            }

            // جلب موظفي التغطية الحاليين
            $coverageAttendances = Attendance::with('employee')
                ->where('zone_id', $zoneId)
                ->where('status', 'coverage')
                ->whereNull('check_out')
                ->whereDate('date', $date)
                ->get();

            // تحضير النتيجة لموظفي التغطية
            $coverageEmployees = $coverageAttendances->map(function ($attendance) {
                $employee = $attendance->employee;

                return [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->first_name.' '.$employee->father_name.' '.$employee->family_name,
                    'status' => 'coverage',
                    'check_in' => $attendance->check_in,
                    'check_out' => null,
                    'mobile_number' => $employee->mobile_number,
                    'phone_number' => $employee->phone_number,
                    'notes' => $attendance->notes,
                    'is_coverage' => true,
                    'out_of_zone' => $employee ? $employee->out_of_zone : false,
                ];
            });

            // دمج النتائج
            $allEmployees = $regularEmployees->concat($coverageEmployees);

            return response()->json([
                'status' => 'success',
                'data' => $allEmployees,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء جلب بيانات الحضور',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * جلب حالة الحضور للموظفين في مشروع معين ومنطقة معينة.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAttendanceStatusV2(Request $request)
{
    $request->validate([
        'project_id' => 'required|integer',
        'zone_id' => 'required|integer',
        'date' => 'required|date',
    ]);

    try {
        $projectId = $request->input('project_id');
        $zoneId = $request->input('zone_id');
        $date = Carbon::parse($request->input('date'));

        // جلب جميع سجلات الربط بين الموظفين والمشروع والمنطقة والتاريخ المحدد
        $employeeRecords = EmployeeProjectRecord::with('employee')
            ->where('project_id', $projectId)
            ->where('zone_id', $zoneId)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $date);
            })
            ->where('start_date', '<=', $date)
            ->get();

        $employees = $employeeRecords->map(function ($record) {
            return $record->employee;
        });

        // جلب كل سجلات الحضور لهذا اليوم والمنطقة (بكل الورديات)
        $attendanceRecords = Attendance::where('zone_id', $zoneId)
            ->whereDate('date', $date)
            ->get();

        $regularEmployees = $employees->map(function ($employee) use ($attendanceRecords) {
            $attendance = $attendanceRecords->firstWhere('employee_id', $employee->id);

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->first_name.' '.$employee->father_name.' '.$employee->family_name,
                'status' => $attendance ? $attendance->status : 'absent',
                'check_in' => $attendance ? $attendance->check_in : null,
                'check_out' => $attendance ? $attendance->check_out : null,
                'shift_id' => $attendance ? $attendance->shift_id : null,
                'mobile_number' => $employee->mobile_number,
                'phone_number' => $employee->phone_number,
                'notes' => $attendance ? $attendance->notes : null,
                'is_coverage' => $attendance && $attendance->status === 'coverage',
                'out_of_zone' => $employee ? $employee->out_of_zone : false,
            ];
        });

        // جلب موظفي التغطية الذين لم يتم تسجيلهم في EmployeeProjectRecord (ربما غير مرتبطين بالمشروع)
        $coverageAttendances = Attendance::with('employee')
            ->where('zone_id', $zoneId)
            ->where('status', 'coverage')
            ->whereDate('date', $date)
            ->get();

        $coverageEmployees = $coverageAttendances->map(function ($attendance) {
            $employee = $attendance->employee;

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->first_name.' '.$employee->father_name.' '.$employee->family_name,
                'status' => 'coverage',
                'check_in' => $attendance->check_in,
                'check_out' => $attendance->check_out,
                'shift_id' => $attendance->shift_id,
                'mobile_number' => $employee->mobile_number,
                'phone_number' => $employee->phone_number,
                'notes' => $attendance->notes,
                'is_coverage' => true,
                'out_of_zone' => $employee ? $employee->out_of_zone : false,
            ];
        });

        // دمج النتائج وإزالة التكرار إن وجد
        $allEmployees = $regularEmployees->merge($coverageEmployees)->unique('employee_id')->values();

        return response()->json([
            'status' => 'success',
            'data' => $allEmployees,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'حدث خطأ أثناء جلب بيانات الحضور',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
