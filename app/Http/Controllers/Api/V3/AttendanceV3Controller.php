<?php

namespace App\Http\Controllers\Api\V3;

use App\Events\NewNotification;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\EmployeeProjectRecord;
use App\Models\EmployeeStatus;
use App\Models\Shift;
use App\Models\User;
use App\Models\Zone;
use App\Notifications\CoverageRequestNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;


class AttendanceV3Controller extends Controller
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

        //  return response()->json([
        //             'message' => 'يرجى تحديث التطبيق إلى أحدث إصدار لتسجيل الحضور.',

        //         ], 400);

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

        $isFirstRealAttendance = Attendance::where('employee_id', $employee->id)
            ->whereIn('status', ['present', 'coverage']) // فقط نبحث عن حضور أو تغطية
            ->where('id', '!=', $attendance->id) // استثناء السجل الحالي (اللي سجلناه للتو)
            ->doesntExist();

        if ($isFirstRealAttendance) {
            try {
                dispatch(new \App\Jobs\SendFirstAttendanceEmail($employee, $attendance->zone, $attendance->date));
            } catch (\Throwable $e) {
                // سجل الخطأ في اللوج بدون أن توقف العملية
                \Log::error('فشل إرسال إشعار مباشرة الموظف: '.$e->getMessage(), [
                    'employee_id' => $employee->id,
                    'zone_id' => $attendance->zone->id ?? null,
                    'date' => $attendance->date,
                ]);
            }
        }

        $expectedStartTime = Carbon::createFromFormat('H:i', $request->input('expected_start_time'), 'Asia/Riyadh');
        $currentTime = Carbon::now('Asia/Riyadh');

        // نتحقق فقط إذا الوقت الحالي بعد المتوقع
        $lateMinutes = $currentTime->greaterThan($expectedStartTime)
            ? $expectedStartTime->diffInMinutes($currentTime)
            : 0;

        // \Log::info('حساب وقت التأخير', [
        //     'employee_id' => $employee->id,
        //     'expected_start_time' => $expectedStartTime->toTimeString(),
        //     'current_time' => $currentTime->toTimeString(),
        //     'late_minutes' => $lateMinutes,
        // ]);

        if ($lateMinutes >= 60) {
            // إطلاق إشعار واتساب دون التأثير على التحضير
            try {
                dispatch(new \App\Jobs\SendLateCheckInWhatsapp($employee, $lateMinutes));
                \Log::info('جدولة رسالة التأخير عبر واتساب', [
                    'employee_id' => $employee->id,
                    'late_minutes' => $lateMinutes,
                ]);
            } catch (\Throwable $e) {
                \Log::error('فشل جدولة رسالة التأخير عبر واتساب', [
                    'employee_id' => $employee->id,
                    'late_minutes' => $lateMinutes,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->updateEmployeeStatusOnCheckIn($employee);

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
              'shift_id' => ['sometimes', 'nullable', 'integer', Rule::exists('shifts', 'id')
        // (اختياري) تأكيد أن الوردية تتبع نفس الـ zone:
        ->where(fn ($q) => $q->where('zone_id', $request->zone_id))
    ],
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
            'shift_id'           => $request->input('shift_id'),
            'date' => $date,
            'check_in' => Carbon::now('Asia/Riyadh')->toTimeString(),
            'check_in_datetime' => $currentDateTime,
            'status' => 'coverage',
            'is_coverage' => true,
            'notes' => $request->input('notes'),
        ]);

        $this->updateEmployeeStatusOnCheckIn($employee);

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
        $isFirstRealAttendance = Attendance::where('employee_id', $employee->id)
            ->whereIn('status', ['present', 'coverage']) // فقط نبحث عن حضور أو تغطية
            ->where('id', '!=', $attendance->id) // استثناء السجل الحالي (اللي سجلناه للتو)
            ->doesntExist();

        if ($isFirstRealAttendance) {
            try {
                dispatch(new \App\Jobs\SendFirstAttendanceEmail($employee, $attendance->zone, $attendance->date));
            } catch (\Throwable $e) {
                // سجل الخطأ في اللوج بدون أن توقف العملية
                \Log::error('فشل إرسال إشعار مباشرة الموظف: '.$e->getMessage(), [
                    'employee_id' => $employee->id,
                    'zone_id' => $attendance->zone->id ?? null,
                    'date' => $attendance->date,
                ]);
            }
        }
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

         try{
            // تسجيل وقت اول نقص في الموقع 
           $this->checkZoneUnattendedStart($attendance->zone_id);
        } catch (Exception $e) {
            
            \Log::error('Error checking zone unattended start', [
                'error' => $e->getMessage(),
                'zone_id' => $attendance->zone_id,
            ]);
        }

        // تحديث بيانات الانصراف
        $attendance->update([
            'check_out' => $currentDateTime->toTimeString(), // العمود القديم
            'check_out_datetime' => $currentDateTime, // العمود الجديد
            'work_hours' => $workHours,
            'notes' => $attendance->notes.' | '.$request->input('notes'),
            'auto_checked_out' => $request->boolean('auto_checked_out', false), // جديد: حقل لتحديد ما إذا كان الخروج تلقائيًا
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

        try{
            // تسجيل وقت اول نقص في الموقع 
           $this->checkZoneUnattendedStart($attendance->zone_id);
        } catch (Exception $e) {
            
            \Log::error('Error checking zone unattended start', [
                'error' => $e->getMessage(),
                'zone_id' => $attendance->zone_id,
            ]);
        }

        // تحديث بيانات الانصراف للتغطية
        $attendance->update([
            'check_out' => $currentDateTime->toTimeString(),
            'check_out_datetime' => $currentDateTime,
            'work_hours' => $workHours,
            'notes' => $attendance->notes.' | '.$request->input('notes'),
            'auto_checked_out' => $request->boolean('auto_checked_out', false),
        ]);

     


        return response()->json([
            'message' => 'Checked out successfully for coverage.',
            'attendance' => $attendance,
        ]);
    }

    /*************  ✨ Windsurf Command ⭐  *************/
    /**
     * Sync check-in for given employee, zone, shift, and morning flag.
     *
     * @return \Illuminate\Http\Response
     */
    /*******  1cb2e983-5a47-4f3c-870a-22fc79a5efc7  *******/
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

        $this->updateEmployeeStatusOnCheckIn($employee);

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
            $date = Carbon::parse($request->input('date'))->toDateString();
            $currentTime = Carbon::now('Asia/Riyadh');

            // جلب بيانات الحالة اللحظية للموظفين الذين حضروا أو لديهم تغطية اليوم
            $threshold = now()->subHours(12);
            $employeeStatuses = EmployeeStatus::with('employee:id,first_name,father_name,grandfather_name,family_name,mobile_number')
                ->whereHas('employee.attendances', function ($query) use ($threshold) {
                    $query->where(function ($q) use ($threshold) {
                        $q->where('check_in_datetime', '>=', $threshold)
                            ->orWhere(function ($q2) use ($threshold) {
                                $q2->where('is_coverage', true)
                                    ->where('created_at', '>=', $threshold);
                            });
                    });
                })
                ->get()
                ->keyBy('employee_id');

            // جلب الورديات المرتبطة بالموقع
            $shifts = Shift::with(['attendances.employee', 'zone.project'])
                ->where('status', 1)
                ->whereHas('zone', function ($query) use ($projectId) {
                    $query->where('status', 1)
                        ->where('project_id', $projectId)
                        ->whereHas('project', function ($q) {
                            $q->where('status', 1);
                        });
                })
                ->where('zone_id', $zoneId)
                ->get();

            $dataByShift = [];

            foreach ($shifts as $shift) {
                // جلب الموظفين المرتبطين بالسجل في هذا التاريخ
                $employeeRecords = EmployeeProjectRecord::with('employee')
                    ->where('project_id', $projectId)
                    ->where('zone_id', $zoneId)
                    ->where('shift_id', $shift->id)
                    ->where('status', true)
                    ->where(function ($query) use ($date) {
                        $query->whereNull('end_date')->orWhere('end_date', '>=', $date);
                    })
                    ->where('start_date', '<=', $date)
                    ->whereHas('employee', function ($q) {
                        $q->where('status', 1);
                    })
                    ->get();

                $attendances = $shift->attendances->where('date', $date);

                $employees = $employeeRecords->map(function ($record) use ($attendances, $employeeStatuses, $date) {
                    $attendance = $attendances->firstWhere('employee_id', $record->employee_id);
                    $employee = $record->employee;
                    $statusData = $employeeStatuses[$employee->id] ?? null;

                    // ⬅️ جلب جميع التغطيات التي قام بها الموظف اليوم (حتى لو مكررة)
                    $coveragesToday = \App\Models\Attendance::with('zone.project', 'shift')
                        ->where('employee_id', $record->employee_id)
                        ->where('status', 'coverage')
                        ->whereDate('date', $date)
                        ->get()
                        ->map(function ($cov) {
                            return [
                                'zone_name' => $cov->zone->name ?? 'غير معروف',
                                'project_name' => $cov->zone->project->name ?? 'غير معروف',
                                'shift_name' => $cov->shift->name ?? 'غير معروف',
                                'check_in' => $cov->check_in,
                                'check_out' => $cov->check_out,
                            ];
                        });

                    return [
                        'employee_id' => $record->employee_id,
                        'employee_name' => $employee->name(),
                        'status' => $attendance?->status ?? 'absent',
                        'check_in' => $attendance?->check_in,
                        'check_out' => $attendance?->check_out,
                        'notes' => $attendance?->notes,
                        'mobile_number' => $employee->mobile_number,
                        'is_coverage' => false,
                        'out_of_zone' => $employee->out_of_zone,
                        'is_checked_in' => $attendance !== null,
                        'is_late' => $attendance?->is_late ?? false,
                        'gps_enabled' => $statusData?->gps_enabled ?? null,
                        'is_inside' => $statusData?->is_inside ?? null,
                        'last_seen_at' => optional($statusData?->last_seen_at)?->toDateTimeString(),
                        // ✅ التغطيات التي قام بها الموظف اليوم
                        'coverages_today' => $coveragesToday,
                    ];
                });

                // تحديد نوع الوردية (1 = صباح، 2 = مساء)
                $shiftType = $shift->shift_type;
                $startTime = $shiftType === 1 ? $shift->morning_start : $shift->evening_start;
                $endTime = $shiftType === 1 ? $shift->morning_end : $shift->evening_end;

                // هل هو يوم عمل؟
                $isWorkingDay = $shift->isWorkingDay2(Carbon::parse($date.' 00:00:00', 'Asia/Riyadh'));

                $isCurrentShift = $this->isCurrentShift($shift, $currentTime, $shift->zone);

                $dataByShift[] = [
                    'shift_id' => $shift->id,
                    'shift_name' => $shift->name,
                    'is_current_shift' => $isCurrentShift,
                    'is_working_day' => $isWorkingDay,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'employees' => $employees,
                ];
            }

            // التغطيات النشطة
            $coverageAttendances = Attendance::with('employee')
                ->where('zone_id', $zoneId)
                ->where('status', 'coverage')
                ->whereDate('date', $date)
                ->whereHas('employee', function ($q) {
                    $q->where('status', 1);
                })
                ->get();

            $coverageEmployees = $coverageAttendances->map(function ($attendance) use ($employeeStatuses, $date) {
                $employee = $attendance->employee;
                $statusData = $employeeStatuses[$employee->id] ?? null;

                // جلب سجل الإسناد الصحيح بناءً على تاريخ الحضور
                $assignment = \App\Models\EmployeeProjectRecord::with(['project', 'zone', 'shift'])
                    ->where('employee_id', $employee->id)
                    ->where('start_date', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
                    })
                    ->latest('start_date') // في حال وجود أكثر من سجل، نأخذ الأحدث
                    ->first();

                return [
                    'employee_id' => $attendance->employee_id,
                    'employee_name' => $employee->name(),
                    'status' => 'coverage',
                    'check_in' => $attendance->check_in,
                    'check_out' => $attendance->check_out,
                    'notes' => $attendance->notes,
                    'mobile_number' => $employee->mobile_number,
                    'is_coverage' => true,
                    'out_of_zone' => $employee->out_of_zone,
                    'is_checked_in' => true,
                    'is_late' => false,
                    'gps_enabled' => $statusData?->gps_enabled ?? null,
                    'is_inside' => $statusData?->is_inside ?? null,
                    'last_seen_at' => optional($statusData?->last_seen_at)?->toDateTimeString(),

                    // ✅ معلومات الإسناد الرسمي، وليس الموقع الفعلي للتغطية
                    'project_name' => $assignment?->project?->name ?? 'غير معروف',
                    'zone_name' => $assignment?->zone?->name ?? 'غير معروف',
                    'shift_name' => $assignment?->shift?->name ?? 'غير معروف',
                ];
            });

            return response()->json([
                'status' => 'success',
                'date' => $date,
                'zone_id' => $zoneId,
                'data' => [
                    'shifts' => $dataByShift,
                    'coverage' => $coverageEmployees,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء جلب بيانات الحضور',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function isCurrentShift($shift, $currentTime, $zone)
    {
        // تحقق من إذا كان اليوم يوم عمل
        $isWorkingDay = $shift->isWorkingDay();

        // نحصل على تاريخ اليوم من $currentTime
        $today = $currentTime->toDateString();

        // $morningStart = Carbon::parse("$today {$shift->morning_start}", 'Asia/Riyadh');
        // $morningEnd = Carbon::parse("$today {$shift->morning_end}", 'Asia/Riyadh');
        // $eveningStart = Carbon::parse("$today {$shift->evening_start}", 'Asia/Riyadh');
        // $eveningEnd = Carbon::parse("$today {$shift->evening_end}", 'Asia/Riyadh');
        // if ($eveningEnd->lessThan($eveningStart)) {
        //     $eveningEnd->addDay();
        // }

        // إنشاء أوقات الوردية مع التاريخ
        $morningStart = Carbon::parse("$today {$shift->morning_start}", 'Asia/Riyadh');
        $morningEnd = Carbon::parse("$today {$shift->morning_end}", 'Asia/Riyadh');
        $eveningStart = Carbon::parse("$today {$shift->evening_start}", 'Asia/Riyadh');
        $eveningEnd = Carbon::parse("$today {$shift->evening_end}", 'Asia/Riyadh');

        // التحقق من الفترة التي تمتد عبر منتصف الليل وإضافة يوم لنهاية الفترة إذا لزم الأمر
        if ($eveningEnd->lessThan($eveningStart)) {
            $eveningEnd->addDay();
        }
        $isWithinShiftTime = false;

        switch ($shift->type) {
            case 'morning':
                $isWithinShiftTime = $currentTime->between($morningStart, $morningEnd);
                break;

            case 'evening':
                $isWithinShiftTime = $currentTime->between($eveningStart, $eveningEnd);
                break;

            case 'morning_evening':
                $isWithinShiftTime = $this->determineShiftCycle($shift, $currentTime, $morningStart, $morningEnd, $eveningStart, $eveningEnd, 'morning_evening');
                break;

            case 'evening_morning':
                $isWithinShiftTime = $this->determineShiftCycle($shift, $currentTime, $morningStart, $morningEnd, $eveningStart, $eveningEnd, 'evening_morning');
                break;
        }

        // الشرط النهائي
        return $isWorkingDay && $isWithinShiftTime;
    }

    private function determineShiftCycle($shift, $currentTime, $morningStart, $morningEnd, $eveningStart, $eveningEnd, $type)
    {
        // دورة العمل = عدد أيام العمل + الإجازة

        if (! $shift->zone || ! $shift->zone->pattern) {
            // إذا لم تكن هناك بيانات كافية
            return false;
        }

        $pattern = $shift->zone->pattern;

        $cycleLength = $pattern->working_days + $pattern->off_days;

        // تحقق إذا كانت دورة العمل غير صالحة (صفر أو أقل)
        if ($cycleLength <= 0) {
            throw new Exception('Cycle length must be greater than zero. Please check the working_days and off_days values.');
        }

        // تاريخ بداية الوردية
        // $startDate = Carbon::parse($shift->start_date)->startOfDay();
        $startDate = Carbon::parse($shift->start_date, 'Asia/Riyadh')->startOfDay();

        // عدد الأيام منذ تاريخ البداية
        $daysSinceStart = $startDate->diffInDays(Carbon::today('Asia/Riyadh'));

        // رقم الدورة الحالية
        $currentCycleNumber = (int) floor($daysSinceStart / $cycleLength) + 1;

        // اليوم الحالي داخل الدورة
        $currentDayInCycle = $daysSinceStart % $cycleLength;

        // إذا كان اليوم الحالي داخل أيام العمل
        $isWorkingDayInCycle = $currentDayInCycle < $pattern->working_days;

        // تحديد إذا كانت الدورة الحالية فردية أو زوجية
        $isOddCycle = $currentCycleNumber % 2 == 1;
        // if ($shift->name == 'الوردية الاولى A' && $shift->zone->name == 'موقع شركة ENPPI الجعيمة') {
        //     \Log::info('isOddCycle', ['isOddCycle' => $isOddCycle, 'currentCycleNumber' => $currentCycleNumber]);
        // }

        // تحديد الوردية الحالية بناءً على نوعها
        if ($type === 'morning_evening') {
            // دورة فردية: صباحية، دورة زوجية: مسائية
            return $isWorkingDayInCycle && (
                ($isOddCycle && $currentTime->between($morningStart, $morningEnd)) ||
                ((! $isOddCycle) && $currentTime->between($eveningStart, $eveningEnd))
            );
        }

        if ($type === 'evening_morning') {
            // دورة فردية: مسائية، دورة زوجية: صباحية
            return $isWorkingDayInCycle && (
                ($isOddCycle && $currentTime->between($eveningStart, $eveningEnd)) ||
                ((! $isOddCycle) && $currentTime->between($morningStart, $morningEnd))
            );
        }

        return false; // الأنواع الأخرى ليست متداخلة
    }

    /**
     * دالة محسنة لعرض حالة الحضور للموظفين مع مراعاة الورديات من اليوم السابق
     * تعالج مشكلة عرض الورديات المناسبة بعد منتصف الليل
     */
    public function getAttendanceStatusV3(Request $request)
    {
        $request->validate([
            'project_id' => 'required|integer',
            'zone_id' => 'required|integer',
            'date' => 'required|date',
        ]);

        try {
            $projectId = $request->input('project_id');
            $zoneId = $request->input('zone_id');
            $requestDate = Carbon::parse($request->input('date'))->toDateString();
            $currentTime = Carbon::now('Asia/Riyadh');

            // تحديد اليوم السابق للتاريخ المطلوب
            $previousDate = Carbon::parse($requestDate)->subDay()->toDateString();

            // جلب بيانات الحالة اللحظية للموظفين الذين حضروا أو لديهم تغطية
            $threshold = now()->subHours(12);
            $employeeStatuses = EmployeeStatus::with('employee:id,first_name,father_name,grandfather_name,family_name,mobile_number')
                ->whereHas('employee.attendances', function ($query) use ($threshold) {
                    $query->where(function ($q) use ($threshold) {
                        $q->where('check_in_datetime', '>=', $threshold)
                            ->orWhere(function ($q2) use ($threshold) {
                                $q2->where('is_coverage', true)
                                    ->where('created_at', '>=', $threshold);
                            });
                    });
                })
                ->get()
                ->keyBy('employee_id');

            // جلب الورديات المرتبطة بالموقع
            $shifts = Shift::with('attendances.employee')
                ->where('zone_id', $zoneId)
                ->get();

            $dataByShift = [];
            $activeShifts = $this->getActiveShiftsForAttendance($shifts, $currentTime, $requestDate, $previousDate);

            foreach ($shifts as $shift) {
                // تحديد ما إذا كانت الوردية نشطة حاليًا
                $shiftInfo = $activeShifts->firstWhere('shift_id', $shift->id);
                $isCurrentShift = ! is_null($shiftInfo);
                $relevantDate = $isCurrentShift ? $shiftInfo['attendance_date'] : $requestDate;

                // جلب الموظفين المرتبطين بالسجل في التاريخ المناسب
                $employeeRecords = EmployeeProjectRecord::with('employee')
                    ->where('project_id', $projectId)
                    ->where('zone_id', $zoneId)
                    ->where('shift_id', $shift->id)
                    ->where(function ($query) use ($relevantDate) {
                        $query->whereNull('end_date')->orWhere('end_date', '>=', $relevantDate);
                    })
                    ->where('start_date', '<=', $relevantDate)
                    ->get();

                // جلب سجلات الحضور للتاريخ المناسب
                $attendances = $shift->attendances->where('date', $relevantDate);

                $employees = $employeeRecords->map(function ($record) use ($attendances, $employeeStatuses) {
                    $attendance = $attendances->firstWhere('employee_id', $record->employee_id);
                    $employee = $record->employee;
                    $statusData = $employeeStatuses[$employee->id] ?? null;

                    return [
                        'employee_id' => $record->employee_id,
                        'employee_name' => $employee->name(),
                        'status' => $attendance?->status ?? 'absent',
                        'check_in' => $attendance?->check_in,
                        'check_out' => $attendance?->check_out,
                        'notes' => $attendance?->notes,
                        'mobile_number' => $employee->mobile_number,
                        'is_coverage' => false,
                        'out_of_zone' => $employee->out_of_zone,
                        'is_checked_in' => $attendance !== null,
                        'is_late' => $attendance?->is_late ?? false,
                        'gps_enabled' => $statusData?->gps_enabled ?? null,
                        'is_inside' => $statusData?->is_inside ?? null,
                        'last_seen_at' => optional($statusData?->last_seen_at)?->toDateTimeString(),
                    ];
                });

                // تحديد نوع الوردية (1 = صباح، 2 = مساء)
                $shiftType = $shift->shift_type;
                $startTime = $shiftType === 1 ? $shift->morning_start : $shift->evening_start;
                $endTime = $shiftType === 1 ? $shift->morning_end : $shift->evening_end;

                // هل هو يوم عمل؟
                $isWorkingDay = $shift->isWorkingDay2(Carbon::parse($relevantDate.' 00:00:00', 'Asia/Riyadh'));

                $dataByShift[] = [
                    'shift_id' => $shift->id,
                    'shift_name' => $shift->name,
                    'is_current_shift' => $isCurrentShift,
                    'is_working_day' => $isWorkingDay,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'attendance_date' => $relevantDate, // إضافة تاريخ الحضور المناسب
                    'employees' => $employees,
                ];
            }

            // ترتيب الورديات بحيث تظهر الورديات النشطة أولاً
            usort($dataByShift, function ($a, $b) {
                if ($a['is_current_shift'] && ! $b['is_current_shift']) {
                    return -1;
                }
                if (! $a['is_current_shift'] && $b['is_current_shift']) {
                    return 1;
                }

                return 0;
            });
            $timezone = 'Asia/Riyadh';
            $nowInRiyadh = Carbon::now($timezone);
            // التغطيات النشطة - جلب التغطيات من اليوم الحالي واليوم السابق
            $coverageAttendances = Attendance::with('employee')
                ->where('zone_id', $zoneId)
                ->where('status', 'coverage')
                ->whereIn('date', [$requestDate, $previousDate])
                ->whereNull('check_out') // فقط التغطيات النشطة (بدون تسجيل انصراف)
                ->where('check_in', '>=', $nowInRiyadh->subHours(16)->timezone('UTC')) // فقط التغطيات التي مضى عليها أقل من 12 ساعة
                ->get();

            $coverageEmployees = $coverageAttendances->map(function ($attendance) use ($employeeStatuses) {
                $employee = $attendance->employee;
                $statusData = $employeeStatuses[$employee->id] ?? null;

                return [
                    'employee_id' => $attendance->employee_id,
                    'employee_name' => $employee->name(),
                    'status' => 'coverage',
                    'check_in' => $attendance->check_in,
                    'check_out' => $attendance->check_out,
                    'notes' => $attendance->notes,
                    'mobile_number' => $employee->mobile_number,
                    'is_coverage' => true,
                    'out_of_zone' => $employee->out_of_zone,
                    'is_checked_in' => true,
                    'is_late' => false,
                    'gps_enabled' => $statusData?->gps_enabled ?? null,
                    'is_inside' => $statusData?->is_inside ?? null,
                    'last_seen_at' => optional($statusData?->last_seen_at)?->toDateTimeString(),
                    'attendance_date' => $attendance->date, // إضافة تاريخ الحضور
                ];
            });

            return response()->json([
                'status' => 'success',
                'date' => $requestDate,
                'zone_id' => $zoneId,
                'data' => [
                    'shifts' => $dataByShift,
                    'coverage' => $coverageEmployees,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء جلب بيانات الحضور',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAttendanceStatusV4(Request $request)
    {
        $request->validate([
            'project_id' => 'required|integer',
            'zone_id' => 'required|integer',
            'date' => 'required|date',
        ]);

        try {
            $projectId = $request->input('project_id');
            $zoneId = $request->input('zone_id');
            $date = Carbon::parse($request->input('date'))->toDateString();
            $currentTime = Carbon::now('Asia/Riyadh');

            $threshold = now()->subHours(12);
            $employeeStatuses = EmployeeStatus::with('employee:id,first_name,father_name,grandfather_name,family_name,mobile_number')
                ->whereHas('employee.attendances', function ($query) use ($threshold) {
                    $query->where(function ($q) use ($threshold) {
                        $q->where('check_in_datetime', '>=', $threshold)
                            ->orWhere(function ($q2) use ($threshold) {
                                $q2->where('is_coverage', true)
                                    ->where('created_at', '>=', $threshold);
                            });
                    });
                })
                ->get()
                ->keyBy('employee_id');

            $shifts = Shift::with(['attendances.employee', 'zone.project'])
                ->where('status', 1)
                ->whereHas('zone', function ($query) use ($projectId) {
                    $query->where('status', 1)
                        ->where('project_id', $projectId)
                        ->whereHas('project', function ($q) {
                            $q->where('status', 1);
                        });
                })
                ->where('zone_id', $zoneId)
                ->get();

            $dataByShift = [];

            foreach ($shifts as $shift) {
                $employeeRecords = EmployeeProjectRecord::with('employee')
                    ->where('project_id', $projectId)
                    ->where('zone_id', $zoneId)
                    ->where('shift_id', $shift->id)
                    ->where('status', true)
                    ->where(function ($query) use ($date) {
                        $query->whereNull('end_date')->orWhere('end_date', '>=', $date);
                    })
                    ->where('start_date', '<=', $date)
                    ->whereHas('employee', function ($q) {
                        $q->where('status', 1);
                    })
                    ->get();

                $attendances = $shift->attendances->where('date', $date);

                $employees = $employeeRecords->map(function ($record) use ($attendances, $employeeStatuses, $date) {
                    $attendance = $attendances->firstWhere('employee_id', $record->employee_id);
                    $employee = $record->employee;
                    $statusData = $employeeStatuses[$employee->id] ?? null;

                    $coveragesToday = \App\Models\Attendance::with('zone.project', 'shift')
                        ->where('employee_id', $record->employee_id)
                        ->where('status', 'coverage')
                        ->whereDate('date', $date)
                        ->get()
                        ->map(function ($cov) {
                            return [
                                'zone_name' => $cov->zone->name ?? 'غير معروف',
                                'project_name' => $cov->zone->project->name ?? 'غير معروف',
                                'shift_name' => $cov->shift->name ?? 'غير معروف',
                                'check_in' => $cov->check_in,
                                'check_out' => $cov->check_out,
                            ];
                        });

                    return [
                        'employee_id' => $record->employee_id,
                        'employee_name' => $employee->name(),
                        'status' => $attendance?->status ?? 'absent',
                        'check_in' => $attendance?->check_in,
                        'check_out' => $attendance?->check_out,
                        'notes' => $attendance?->notes,
                        'mobile_number' => $employee->mobile_number,
                        'is_coverage' => false,
                        'out_of_zone' => $employee->out_of_zone,
                        'is_checked_in' => $attendance !== null,
                        'is_late' => $attendance?->is_late ?? false,
                        'gps_enabled' => $statusData?->gps_enabled ?? null,
                        'is_inside' => $statusData?->is_inside ?? null,
                        'last_seen_at' => optional($statusData?->last_seen_at)?->toDateTimeString(),
                        'coverages_today' => $coveragesToday,
                    ];
                });

                $shiftType = $shift->shift_type;
                $startTime = $shiftType === 1 ? $shift->morning_start : $shift->evening_start;
                $endTime = $shiftType === 1 ? $shift->morning_end : $shift->evening_end;

                // ✅ استخدام الدالة الجديدة بدلًا من isWorkingDay2 مباشرة
                $isWorkingDay = $this->adjustedIsWorkingDay($shift, Carbon::parse($date.' 00:00:00', 'Asia/Riyadh'), $currentTime);

                $isCurrentShift = $this->isCurrentShift($shift, $currentTime, $shift->zone);

                $dataByShift[] = [
                    'shift_id' => $shift->id,
                    'shift_name' => $shift->name,
                    'is_current_shift' => $isCurrentShift,
                    'is_working_day' => $isWorkingDay,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'employees' => $employees,
                ];
            }

            $coverageAttendances = Attendance::with('employee')
                ->where('zone_id', $zoneId)
                ->where('status', 'coverage')
                ->whereDate('date', $date)
                ->whereHas('employee', function ($q) {
                    $q->where('status', 1);
                })
                ->get();

            $coverageEmployees = $coverageAttendances->map(function ($attendance) use ($employeeStatuses, $date) {
                $employee = $attendance->employee;
                $statusData = $employeeStatuses[$employee->id] ?? null;

                $assignment = \App\Models\EmployeeProjectRecord::with(['project', 'zone', 'shift'])
                    ->where('employee_id', $employee->id)
                    ->where('start_date', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
                    })
                    ->latest('start_date')
                    ->first();

                return [
                    'employee_id' => $attendance->employee_id,
                    'employee_name' => $employee->name(),
                    'status' => 'coverage',
                    'check_in' => $attendance->check_in,
                    'check_out' => $attendance->check_out,
                    'notes' => $attendance->notes,
                    'mobile_number' => $employee->mobile_number,
                    'is_coverage' => true,
                    'out_of_zone' => $employee->out_of_zone,
                    'is_checked_in' => true,
                    'is_late' => false,
                    'gps_enabled' => $statusData?->gps_enabled ?? null,
                    'is_inside' => $statusData?->is_inside ?? null,
                    'last_seen_at' => optional($statusData?->last_seen_at)?->toDateTimeString(),
                    'project_name' => $assignment?->project?->name ?? 'غير معروف',
                    'zone_name' => $assignment?->zone?->name ?? 'غير معروف',
                    'shift_name' => $assignment?->shift?->name ?? 'غير معروف',
                ];
            });

            return response()->json([
                'status' => 'success',
                'date' => $date,
                'zone_id' => $zoneId,
                'data' => [
                    'shifts' => $dataByShift,
                    'coverage' => $coverageEmployees,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء جلب بيانات الحضور',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function adjustedIsWorkingDay($shift, Carbon $date, Carbon $now): bool
    {
        $isTodayWorking = $shift->isWorkingDay2($date);

        if (! $isTodayWorking) {
            $yesterday = $date->copy()->subDay();
            $isYesterdayWorking = $shift->isWorkingDay2($yesterday);

            $eveningStart = Carbon::parse("{$yesterday->toDateString()} {$shift->evening_start}", 'Asia/Riyadh');
            $eveningEnd = Carbon::parse("{$yesterday->toDateString()} {$shift->evening_end}", 'Asia/Riyadh');

            if ($eveningEnd->lessThan($eveningStart)) {
                $eveningEnd->addDay();
            }

            if ($isYesterdayWorking && $now->between($eveningStart, $eveningEnd)) {
                return true;
            }
        }

        return $isTodayWorking;
    }

    public function getAttendanceStatusV5(Request $request)
    {
        $request->validate([
            'project_id' => 'required|integer',
            'zone_id' => 'required|integer',
            'date' => 'required|date',
        ]);

        try {
            $projectId = $request->input('project_id');
            $zoneId = $request->input('zone_id');
            $date = Carbon::parse($request->input('date'))->toDateString();
            $currentTime = Carbon::now('Asia/Riyadh');

            // ⬅️ الحالة اللحظية
            $threshold = now()->subHours(12);
            $employeeStatuses = EmployeeStatus::with('employee:id,first_name,father_name,grandfather_name,family_name,mobile_number')
                ->whereHas('employee.attendances', function ($query) use ($threshold) {
                    $query->where(function ($q) use ($threshold) {
                        $q->where('check_in_datetime', '>=', $threshold)
                            ->orWhere(function ($q2) use ($threshold) {
                                $q2->where('is_coverage', true)
                                    ->where('created_at', '>=', $threshold);
                            });
                    });
                })
                ->get()
                ->keyBy('employee_id');

            $shifts = Shift::with(['attendances.employee', 'zone.project'])
                ->where('status', 1)
                ->whereHas('zone', function ($query) use ($projectId) {
                    $query->where('status', 1)
                        ->where('project_id', $projectId)
                        ->whereHas('project', function ($q) {
                            $q->where('status', 1);
                        });
                })
                ->where('zone_id', $zoneId)
                ->get();

            $dataByShift = [];

            foreach ($shifts as $shift) {
                [$isCurrentShift, $startedAt] = $shift->getShiftActiveStatus2($currentTime);

                // تحديد تاريخ الحضور بناءً على بداية الوردية (اليوم أو أمس)
                $attendanceDate = $startedAt === 'yesterday'
                    ? Carbon::parse($date)->subDay()->toDateString()
                    : $date;

                $attendances = $shift->attendances->where('date', $attendanceDate);

                $employeeRecords = EmployeeProjectRecord::with('employee')
                    ->where('project_id', $projectId)
                    ->where('zone_id', $zoneId)
                    ->where('shift_id', $shift->id)
                    ->where('status', true)
                    ->where(function ($query) use ($attendanceDate) {
                        $query->whereNull('end_date')->orWhere('end_date', '>=', $attendanceDate);
                    })
                    ->where('start_date', '<=', $attendanceDate)
                    ->whereHas('employee', function ($q) {
                        $q->where('status', 1);
                    })
                    ->get();

                $employees = $employeeRecords->map(function ($record) use ($attendances, $employeeStatuses, $attendanceDate) {
                    $attendance = $attendances->firstWhere('employee_id', $record->employee_id);
                    $employee = $record->employee;
                    $statusData = $employeeStatuses[$employee->id] ?? null;

                    $coveragesToday = Attendance::with('zone.project', 'shift')
                        ->where('employee_id', $record->employee_id)
                        ->where('status', 'coverage')
                        ->whereDate('date', $attendanceDate)
                        ->get()
                        ->map(function ($cov) {
                            return [
                                'zone_name' => $cov->zone->name ?? 'غير معروف',
                                'project_name' => $cov->zone->project->name ?? 'غير معروف',
                                'shift_name' => $cov->shift->name ?? 'غير معروف',
                                'check_in' => $cov->check_in,
                                'check_out' => $cov->check_out,
                            ];
                        });

                    return [
                        'employee_id' => $record->employee_id,
                        'employee_name' => $employee->name(),
                        'status' => $attendance?->status ?? 'absent',
                        'check_in' => $attendance?->check_in,
                        'check_out' => $attendance?->check_out,
                        'notes' => $attendance?->notes,
                        'mobile_number' => $employee->mobile_number,
                        'is_coverage' => false,
                        'out_of_zone' => $employee->out_of_zone,
                        'is_checked_in' => $attendance !== null,
                        'is_late' => $attendance?->is_late ?? false,
                        'gps_enabled' => $statusData?->gps_enabled ?? null,
                        'is_inside' => $statusData?->is_inside ?? null,
                        'last_seen_at' => optional($statusData?->last_seen_at)?->toDateTimeString(),
                        'coverages_today' => $coveragesToday,
                    ];
                });

                $shiftType = $shift->shift_type;
                $startTime = $shiftType === 1 ? $shift->morning_start : $shift->evening_start;
                $endTime = $shiftType === 1 ? $shift->morning_end : $shift->evening_end;

                $isWorkingDay = $shift->isWorkingDay2(Carbon::parse($attendanceDate.' 00:00:00', 'Asia/Riyadh'));

                $dataByShift[] = [
                    'shift_id' => $shift->id,
                    'shift_name' => $shift->name,
                    'is_current_shift' => $isCurrentShift,
                    'is_working_day' => $isWorkingDay,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'employees' => $employees,
                ];
            }

            $coverageAttendances = Attendance::with('employee')
                ->where('zone_id', $zoneId)
                ->where('status', 'coverage')
                ->whereDate('date', $date)
                ->whereHas('employee', fn ($q) => $q->where('status', 1))
                ->get();

            $coverageEmployees = $coverageAttendances->map(function ($attendance) use ($employeeStatuses, $date) {
                $employee = $attendance->employee;
                $statusData = $employeeStatuses[$employee->id] ?? null;

                $assignment = EmployeeProjectRecord::with(['project', 'zone', 'shift'])
                    ->where('employee_id', $employee->id)
                    ->where('start_date', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
                    })
                    ->latest('start_date')
                    ->first();

                return [
                    'employee_id' => $attendance->employee_id,
                    'employee_name' => $employee->name(),
                    'status' => 'coverage',
                    'check_in' => $attendance->check_in,
                    'check_out' => $attendance->check_out,
                    'notes' => $attendance->notes,
                    'mobile_number' => $employee->mobile_number,
                    'is_coverage' => true,
                    'out_of_zone' => $employee->out_of_zone,
                    'is_checked_in' => true,
                    'is_late' => false,
                    'gps_enabled' => $statusData?->gps_enabled ?? null,
                    'is_inside' => $statusData?->is_inside ?? null,
                    'last_seen_at' => optional($statusData?->last_seen_at)?->toDateTimeString(),
                    'project_name' => $assignment?->project?->name ?? 'غير معروف',
                    'zone_name' => $assignment?->zone?->name ?? 'غير معروف',
                    'shift_name' => $assignment?->shift?->name ?? 'غير معروف',
                ];
            });

            return response()->json([
                'status' => 'success',
                'date' => $date,
                'zone_id' => $zoneId,
                'data' => [
                    'shifts' => $dataByShift,
                    'coverage' => $coverageEmployees,
                ],
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
     * الحصول على الورديات النشطة حاليًا لعرض حالة الحضور
     * تعالج الورديات من اليوم الحالي واليوم السابق
     */
    private function getActiveShiftsForAttendance($shifts, $currentTime, $requestDate, $previousDate)
    {
        $activeShifts = collect();

        foreach ($shifts as $shift) {
            // التحقق من الورديات النشطة من التاريخ المطلوب
            $requestDateShiftInfo = $this->checkShiftActiveForAttendance($shift, $currentTime, $requestDate);
            if ($requestDateShiftInfo['is_active']) {
                $activeShifts->push([
                    'shift_id' => $shift->id,
                    'attendance_date' => $requestDateShiftInfo['attendance_date'],
                    'cycle_info' => $requestDateShiftInfo['cycle_info'],
                ]);

                continue; // إذا كانت الوردية نشطة في التاريخ المطلوب، لا داعي للتحقق من اليوم السابق
            }

            // التحقق من الورديات النشطة من اليوم السابق
            $previousDateShiftInfo = $this->checkShiftActiveForAttendance($shift, $currentTime, $previousDate);
            if ($previousDateShiftInfo['is_active']) {
                $activeShifts->push([
                    'shift_id' => $shift->id,
                    'attendance_date' => $previousDateShiftInfo['attendance_date'],
                    'cycle_info' => $previousDateShiftInfo['cycle_info'],
                ]);
            }
        }

        return $activeShifts;
    }

    /**
     * التحقق مما إذا كانت الوردية نشطة في تاريخ محدد
     */
    private function checkShiftActiveForAttendance($shift, $currentTime, $checkDate)
    {
        // التحقق من إذا كان اليوم يوم عمل بناءً على نمط العمل
        $isWorkingDay = $this->isWorkingDayInPatternForAttendance($shift, $checkDate);
        if (! $isWorkingDay) {
            return ['is_active' => false, 'attendance_date' => null, 'cycle_info' => null];
        }

        // إنشاء أوقات الوردية مع التاريخ المحدد
        $morningStart = Carbon::parse("$checkDate {$shift->morning_start}", 'Asia/Riyadh');
        $morningEnd = Carbon::parse("$checkDate {$shift->morning_end}", 'Asia/Riyadh');
        $eveningStart = Carbon::parse("$checkDate {$shift->evening_start}", 'Asia/Riyadh');
        $eveningEnd = Carbon::parse("$checkDate {$shift->evening_end}", 'Asia/Riyadh');

        // التعامل مع الورديات التي تمتد عبر منتصف الليل
        if ($eveningEnd->lessThan($eveningStart)) {
            $eveningEnd->addDay();
        }

        // الحصول على معلومات دورة العمل
        $cycleInfo = $this->getShiftCycleInfoForAttendance($shift, $checkDate);
        $isOddCycle = $cycleInfo['is_odd_cycle'];
        $currentCycleNumber = $cycleInfo['current_cycle_number'];
        $currentDayInCycle = $cycleInfo['current_day_in_cycle'];

        $isActive = false;
        $attendanceDate = $checkDate;

        switch ($shift->type) {
            case 'morning':
                $isActive = $currentTime->between($morningStart, $morningEnd);
                break;

            case 'evening':
                $isActive = $currentTime->between($eveningStart, $eveningEnd);
                break;

            case 'morning_evening':
                // دورة فردية: صباحية، دورة زوجية: مسائية
                if ($isOddCycle) {
                    $isActive = $currentTime->between($morningStart, $morningEnd);
                } else {
                    $isActive = $currentTime->between($eveningStart, $eveningEnd);
                }
                break;

            case 'evening_morning':
                // دورة فردية: مسائية، دورة زوجية: صباحية
                if ($isOddCycle) {
                    $isActive = $currentTime->between($eveningStart, $eveningEnd);
                } else {
                    $isActive = $currentTime->between($morningStart, $morningEnd);
                }
                break;
        }

        return [
            'is_active' => $isActive,
            'attendance_date' => $isActive ? $attendanceDate : null,
            'cycle_info' => [
                'is_odd_cycle' => $isOddCycle,
                'current_cycle_number' => $currentCycleNumber,
                'current_day_in_cycle' => $currentDayInCycle,
            ],
        ];
    }

    /**
     * التحقق مما إذا كان اليوم يوم عمل بناءً على نمط العمل
     */
    private function isWorkingDayInPatternForAttendance($shift, $checkDate)
    {
        if (! $shift->zone || ! $shift->zone->pattern) {
            return false;
        }

        $pattern = $shift->zone->pattern;
        $cycleLength = $pattern->working_days + $pattern->off_days;

        if ($cycleLength <= 0) {
            return false;
        }

        // تاريخ بداية الوردية
        $startDate = Carbon::parse($shift->start_date, 'Asia/Riyadh')->startOfDay();
        $checkDateObj = Carbon::parse($checkDate, 'Asia/Riyadh')->startOfDay();

        // عدد الأيام منذ تاريخ البداية
        $daysSinceStart = $startDate->diffInDays($checkDateObj);

        // اليوم الحالي داخل الدورة
        $currentDayInCycle = $daysSinceStart % $cycleLength;

        // إذا كان اليوم الحالي داخل أيام العمل
        return $currentDayInCycle < $pattern->working_days;
    }

    /**
     * الحصول على معلومات دورة العمل للوردية
     */
    private function getShiftCycleInfoForAttendance($shift, $checkDate)
    {
        if (! $shift->zone || ! $shift->zone->pattern) {
            return [
                'is_odd_cycle' => false,
                'current_cycle_number' => 0,
                'current_day_in_cycle' => 0,
            ];
        }

        $pattern = $shift->zone->pattern;
        $cycleLength = $pattern->working_days + $pattern->off_days;

        if ($cycleLength <= 0) {
            return [
                'is_odd_cycle' => false,
                'current_cycle_number' => 0,
                'current_day_in_cycle' => 0,
            ];
        }

        // تاريخ بداية الوردية
        $startDate = Carbon::parse($shift->start_date, 'Asia/Riyadh')->startOfDay();
        $checkDateObj = Carbon::parse($checkDate, 'Asia/Riyadh')->startOfDay();

        // عدد الأيام منذ تاريخ البداية
        $daysSinceStart = $startDate->diffInDays($checkDateObj);

        // رقم الدورة الحالية
        $currentCycleNumber = (int) floor($daysSinceStart / $cycleLength) + 1;

        // اليوم الحالي داخل الدورة
        $currentDayInCycle = $daysSinceStart % $cycleLength;

        // تحديد إذا كانت الدورة الحالية فردية أو زوجية
        $isOddCycle = $currentCycleNumber % 2 == 1;

        return [
            'is_odd_cycle' => $isOddCycle,
            'current_cycle_number' => $currentCycleNumber,
            'current_day_in_cycle' => $currentDayInCycle,
        ];
    }

    private function updateEmployeeStatusOnCheckIn($employee)
    {
        $status = EmployeeStatus::firstOrNew(['employee_id' => $employee->id]);

        $status->last_present_at = now()->toDateString();
        $status->consecutive_absence_count = 0;

        $status->save();
    }

    /**
     * رجوع سجل آخر 7 أيام (اليوم + 6 أيام سابقة) للموظف المصادق.
     * ?days=14  ⟵ يمكنك تمرير عدد أيام مخصّص.
     */
    public function lastWeek(Request $request): JsonResponse
    {
        // 1) التحقق من صلاحيات المشرف (اختياري)
        // $request->user()->can('viewAttendance') …

        // 2) التحقق من وجود employee_id
        $employeeId = $request->integer('employee_id');
        if (! $employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'employee_id is required',
            ], 422);
        }

        $days = max(1, (int) $request->query('days', 7));

        $end = now('Asia/Riyadh')->endOfDay();
        $start = $end->copy()->subDays($days - 1)->startOfDay();

        $records = Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$start, $end])
            ->with(['zone:id,name', 'shift:id,name'])
            ->orderByDesc('date')
            ->orderByDesc('check_in_datetime')
            ->get();

        $last = Attendance::where('employee_id', $employeeId)
            ->latest('check_in_datetime')
            ->first();

        $canCheckIn = false;
        $canCheckOut = false;
        $checkOutType = null;

        if ($last) {
            if ($last->check_out === null) {
                $hoursSinceCheckIn = now('Asia/Riyadh')->diffInHours(Carbon::parse($last->check_in_datetime));

                if ($hoursSinceCheckIn < 12) {
                    $canCheckOut = true;
                    $checkOutType = $last->status === 'coverage' ? 'coverage' : 'attendance';
                } else {
                    $canCheckIn = true;
                }
            } else {
                $canCheckIn = true;
            }
        } else {
            $canCheckIn = true;
        }

        return response()->json([
            'success' => true,
            'data' => \App\Http\Resources\AttendanceResource::collection($records),
            'can_check_in' => $canCheckIn,
            'can_check_out' => $canCheckOut,
            'check_out_type' => $checkOutType,
        ]);
    }

  




function checkZoneUnattendedStart(int $zoneId): void
{
    $zone = Zone::with('shifts')->find($zoneId);
    if (! $zone) return;

    $now = now('Asia/Riyadh');

    $requiredCount = 0;
    $presentCount = 0;

    foreach ($zone->shifts as $shift) {
        [$isCurrent, $startedAt] = $shift->getShiftActiveStatus2($now);
        if (! $isCurrent || ! $shift->status) continue;

        $requiredCount += $shift->emp_no;

        $attendanceDateRange = match ($startedAt) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->endOfDay()],
            default => [null, null],
        };

        if (! $attendanceDateRange[0] || ! $attendanceDateRange[1]) continue;

        $presentCount += Attendance::query()
            ->where('zone_id', $zone->id)
            ->whereIn('status', ['present', 'coverage'])
            ->whereNull('check_out')
            ->whereBetween('created_at', $attendanceDateRange)
            ->count();
    }

    if ($requiredCount === 0) return;

    if ($presentCount < $requiredCount && is_null($zone->last_unattended_started_at)) {
        // ⛔ أول مرة يحصل فيها نقص
        $zone->updateQuietly([
            'last_unattended_started_at' => $now,
        ]);
    }

    if ($presentCount === $requiredCount) {
        // ✅ رجع التغطية كاملة → اعتبره نقطة بداية جديدة
        $zone->updateQuietly([
            'last_unattended_started_at' => $now,
        ]);
    }
}



}
