<?php

use Pusher\Pusher;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Services\AttendanceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AbsentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Api\ZoneController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Api\SlideController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\EmployeeStatusController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\AssignmentReportController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\EmployeeCoordinateController;
use App\Http\Controllers\Api\V2\AttendanceV2Controller;
use App\Http\Controllers\Api\V3\AttendanceV3Controller;
use App\Http\Controllers\attendance\CoverageController;
use App\Http\Controllers\Api\CodeVerificationController;
use App\Http\Controllers\EmployeeNotificationController;
use App\Http\Controllers\Api\AdminNotificationController;
use App\Http\Controllers\Api\V2\UncoveredZonesController;
use App\Http\Controllers\Api\OperationNotificationController;
use App\Http\Controllers\Api\ManulAttendanceController;

Route::post('/install-apk', [App\Http\Controllers\ApkController::class, 'installApk']);
Route::get('/download-apk/{filename}', [App\Http\Controllers\ApkController::class, 'downloadApk']);

Route::get('/all-employees', [EmployeeController::class, 'index'])->middleware('auth:employee');
Route::post('/employee-action', [EmployeeController::class, 'store']);
Route::get('/allowed-employees', [EmployeeController::class, 'allowed'])
    ->middleware('auth:employee');

Route::post('/employee/login', [EmployeeAuthController::class, 'login']);
Route::post('/employee/verify-otp', [EmployeeAuthController::class, 'verifyOtp']);
Route::post('employee/check-device-approval', [App\Http\Controllers\Auth\EmployeeAuthController::class, 'checkDeviceApproval']);
Route::middleware('auth:employee')->get('/me', [EmployeeAuthController::class, 'getEmployeeByToken']);
Route::post('/employee/simple-login', [EmployeeAuthController::class, 'simpleLogin']);

Route::middleware('auth:employee')->post('/employee/change-password', [EmployeeAuthController::class, 'changePassword']);

Route::middleware('auth:employee')->post('/update-player-id', [App\Http\Controllers\Auth\EmployeeAuthController::class, 'updatePlayerId']);

Route::middleware('auth:employee')->group(function () {
    Route::get('/employee/projects', [EmployeeController::class, 'getEmployeeProjects']);
});

Route::middleware('auth:employee')->group(function () {
    Route::get('/employee/projectRecords', [ProjectController::class, 'getEmployeeProjectRecords']);
});

Route::middleware(['auth:employee'])->group(function () {
    Route::get('/employee/zones', [EmployeeController::class, 'getEmployeeZones']);
});

Route::get('zones/coordinates', [ZoneController::class, 'getActiveZonesCoordinates']);
Route::post('zones/details', [ZoneController::class, 'getZoneDetails']);
Route::post('/verify_zones_code', [CodeVerificationController::class, 'verify']);
Route::post('/verify_zones_code_with_attendance', [CodeVerificationController::class, 'verifyWithAttendance']);

Route::get('/employee/attendance/last-week',
    [\App\Http\Controllers\AttendanceController::class, 'lastWeek']
)->middleware('auth:sanctum');

Route::middleware('auth:employee')->get('/employee/notifications', [EmployeeNotificationController::class, 'getNotifications']);

Route::middleware('auth:employee')->get('/employee/notifications/unread-count', [EmployeeNotificationController::class, 'getUnreadCount']);

Route::middleware('auth:employee')->patch('/employee/notifications/{id}/mark-as-read', [EmployeeNotificationController::class, 'markAsRead']);

Route::middleware('auth:employee')->group(function () {
    Route::get('employee/schedule', [EmployeeController::class, 'schedule']);
});

Route::prefix('settings')->group(function () {
    Route::get('/', [SettingsController::class, 'index']); // الحصول على جميع الإعدادات
    Route::post('/', [SettingsController::class, 'update']); // تحديث الإعدادات
});

Route::middleware('auth:employee')->group(function () {
     Route::post('employee/attendance/check-in', [AttendanceController::class, 'checkIn']);  // -----
    Route::post('employee/attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::post('employee/attendance/sync-check-in', [AttendanceController::class, 'syncCheckIn']);
    Route::post('employee/attendance/sync-check-out', [AttendanceController::class, 'syncCheckOut']);
    Route::get('employee/attendance', [AttendanceController::class, 'index']);
    Route::get('attendances/filter', [AttendanceController::class, 'filter']);
    // Route::post('attendances/coverage', [AttendanceController::class, 'store']);
     Route::post('/attendances/coverage/check-in', [AttendanceController::class, 'checkInCoverage']);  //-------
    Route::post('/attendances/coverage/check-out', [AttendanceController::class, 'checkOutCoverage']);

    Route::post('/zones/nearby', [ZoneController::class, 'nearbyZones']);
    Route::post('/zones/nearby-with-shift', [ZoneController::class, 'nearbyZonesWithCurrentShifts']);

    // @deprecated version
    Route::post('/employees/update-zone-status', [EmployeeCoordinateController::class, 'updateZoneStatus']);
});
// routes/api.php
Route::prefix('v2')->middleware('auth:sanctum')->group(function () {
    Route::post('employee/attendance/check-in', [AttendanceV2Controller::class, 'checkIn']);
    Route::post('employee/attendance/check-out', [AttendanceV2Controller::class, 'checkOut']);
});

Route::prefix('v3')->middleware('auth:employee')->group(function () {
  Route::post('employee/attendance/check-in', [AttendanceV3Controller::class, 'checkIn']);
    Route::post('employee/attendance/check-out', [AttendanceV3Controller::class, 'checkOut']);
    Route::post('employee/attendance/sync-check-in', [AttendanceV3Controller::class, 'syncCheckIn']);
    Route::post('employee/attendance/sync-check-out', [AttendanceV3Controller::class, 'syncCheckOut']);
    Route::get('employee/attendance', [AttendanceV3Controller::class, 'index']);
    Route::get('attendances/filter', [AttendanceV3Controller::class, 'filter']);
    // Route::post('attendances/coverage', [AttendanceController::class, 'store']);
    Route::post('/attendances/coverage/check-in', [AttendanceV3Controller::class, 'checkInCoverage']);
    Route::post('/attendances/coverage/check-out', [AttendanceV3Controller::class, 'checkOutCoverage']);

    Route::post('/zones/nearby', [ZoneController::class, 'nearbyZones']);
    Route::post('/zones/nearby-with-shift', [ZoneController::class, 'nearbyZonesWithCurrentShifts']);

    // @deprecated version
    Route::post('/employees/update-zone-status', [EmployeeCoordinateController::class, 'updateZoneStatus']);
});


// routes/api.php

Route::middleware(['auth:employee'])->get('/employee/attendances/recent', [\App\Http\Controllers\Api\EmployeeAttendanceController::class, 'recent']);





Route::post('/zones/nearby-with-shift-operation', [ZoneController::class, 'nearbyZonesWithCurrentShifts']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/employee-route/{employeeId}', [EmployeeCoordinateController::class, 'getEmployeeRoute']);
    Route::get('/employee-locations', [EmployeeCoordinateController::class, 'getRecentEmployeeLocations']);
    Route::get('/employee-locations2', [EmployeeCoordinateController::class, 'getActiveAndInactiveEmployees']);
    Route::get('/zone-employee-routes/{zoneId}', [EmployeeCoordinateController::class, 'getZoneEmployeesRoutes']);

});

Route::middleware('auth:employee')->group(function () {
    Route::post('/coordinates', [EmployeeCoordinateController::class, 'store']);
    Route::get('/coordinates', [EmployeeCoordinateController::class, 'index']);
});

// Route::middleware('auth:employee')->group(function () {
//     Route::post('/employee/check-in', [AttendanceController::class, 'checkIn']);
//     Route::post('/employee/check-out', [AttendanceController::class, 'checkOut']);
// });

Route::middleware('auth:employee')->group(function () {
    Route::post('/employee/attendance', [AttendanceController::class, 'markAttendance']);
});

Route::post('/test-broadcast', function () {
    $testData = [
        'id' => 1,
        'name' => 'Test Area',
        'projects' => [
            [
                'id' => 1,
                'name' => 'Test Project',
                'zones' => [],
            ],
        ],
    ];

    event(new \App\Events\AreasUpdated([$testData]));

    return response()->json(['status' => 'Event broadcasted successfully']);
});

Route::post('/test-notification', function () {
    // بيانات الإشعار التجريبي
    $notificationData = [
        'id' => rand(100, 999), // معرّف عشوائي
        'title' => 'إشعار تجريبي',
        'message' => '📢 لديك إشعار جديد تم إرساله عبر Pusher!',
        'date' => now()->toDateTimeString(),
        'employee_id' => 1,
        'employee_name' => 'محمد عبدالله الربيعي',
        'zone' => 'المنطقة الصناعية',
    ];

    // تهيئة `Pusher`
    $pusher = new Pusher(
        env('PUSHER_APP_KEY'),
        env('PUSHER_APP_SECRET'),
        env('PUSHER_APP_ID'),
        [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'useTLS' => true,
        ]
    );

    // إرسال الحدث إلى قناة `notifications`
    $pusher->trigger('notifications', 'new-notification', $notificationData);

    return response()->json(['status' => 'success', 'message' => 'تم إرسال الإشعار بنجاح!', 'data' => $notificationData]);
});

// Route::get('/areas-with-details', [AreaController::class, 'getAreasWithDetails2'])->middleware('auth:sanctum');
Route::get('/areas-with-details2', [AreaController::class, 'getAreasWithDetailsDynamic'])->middleware('auth:sanctum');
Route::get('/areas/details/improved', [AreaController::class, 'getAreasWithDetailsImproved']);
Route::get('/assigned-employees', [AreaController::class, 'getAssignedEmployeesForShifts']);
Route::get('/attendance', [AttendanceController::class, 'getAttendanceStatus']);
Route::get('/attendance2', [AttendanceController::class, 'getAttendanceStatusV2']);
Route::get('/attendance3', [AttendanceController::class, 'getAttendanceStatusV3']);
Route::get('/attendance4', [AttendanceController::class, 'getAttendanceStatusV']);
Route::get('/attendance5', [AttendanceController::class, 'getAttendanceStatusV5']);  // سيتم حذفها في المستقبل
Route::get('/attendance6', [AttendanceController::class, 'getAttendanceStatusV6']);

Route::get('/active-shifts-summary', [\App\Http\Controllers\Api\ActiveShiftController::class, 'index']);
Route::get('/active-shifts-summary-v2', [\App\Http\Controllers\Api\ActiveShiftController::class, 'indexV2']);

// routes/api.php

Route::get('/assigned-employees-mobiles', [AssignmentReportController::class, 'mobileNumbers']);

Route::get('/slides', [SlideController::class, 'getActiveSlides']);
Route::get('/test-email', [\App\Http\Controllers\TestEmailController::class, 'send']);

Route::post('/run-migrations', function (Request $request) {
    // حماية الوصول بكلمة مرور أو توكن
    $password = $request->input('password');
    if ($password !== env('MIGRATION_PASSWORD', 'default_password')) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // تنفيذ المايجريشن
    try {
        Artisan::call('migrate', ['--force' => true]);

        return response()->json(['message' => 'Migrations executed successfully']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::post('/optimize-project', function (Request $request) {
    // حماية الوصول بكلمة مرور أو توكن
    $password = $request->input('password');
    if ($password !== env('MIGRATION_PASSWORD', 'default_password')) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // تحسين وتنظيف المشروع
    try {
        Artisan::call('optimize:clear');

        return response()->json(['message' => 'Project optimized and cleared successfully']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/process-attendance', function (AttendanceService $attendanceService) {
    $attendanceService->processAttendance();

    return response()->json(['message' => 'Attendance processing started']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function (Request $request) {
    return response()->json(
        [
            'status' => 'success',
            'message' => 'test',
        ]
    );
});

// routes for notifications
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::post('/notifications/send-test', [NotificationController::class, 'sendTestNotification']);
});

Route::prefix('admin')->group(function () {
    Route::post('/notifications/test/all-managers', [AdminNotificationController::class, 'sendTestNotificationToAllManagers']);
    Route::post('/notifications/test/manager/{managerId}', [AdminNotificationController::class, 'sendTestNotificationToManager']);
    Route::get('/notifications/managers', [AdminNotificationController::class, 'getManagersWithNotifications']);
    Route::delete('/notifications/all', [AdminNotificationController::class, 'deleteAllNotifications']);
});

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (! Auth::attempt($credentials)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('flutter-web-token')->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token,
    ]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json($request->user());
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/operation-notifications', [OperationNotificationController::class, 'index']); // استرجاع الإشعارات
    Route::post('/operation-notifications/{id}/read', [OperationNotificationController::class, 'markAsRead']); // وضع علامة مقروء
});

Route::prefix('coverage-requests')->group(function () {
    // ✅ إرسال طلب تغطية جديد
    Route::post('/', [CoverageController::class, 'submitCoverageRequest']);

    // ✅ رفض طلب التغطية
    Route::post('/{attendance_id}/reject', [CoverageController::class, 'rejectCoverageRequest']);

    // ✅ الموافقة على طلب التغطية
    Route::middleware('auth:sanctum')->post('/{attendance_id}/approve', [CoverageController::class, 'approveCoverageRequest']);
});

// ✅ إرجاع قائمة أسباب التغطية والموظفين المتاحين
Route::get('/coverage-reasons', [CoverageController::class, 'getCoverageReasons']);

Route::middleware('auth:employee')->group(function () {
    Route::post('employee/status', [EmployeeStatusController::class, 'updateStatus']);
});
// Route::middleware('auth:sanctum')->group(function () {
Route::get('/employee-statuses', [EmployeeStatusController::class, 'index']);
Route::get('/employee-statuses2', [EmployeeStatusController::class, 'getEmployeeStatusInActiveShifts']);
// });

Route::get('/absent-employees', [AbsentController::class, 'getTrulyAbsentEmployees']);

Route::get('/missing-employees/all', function () {
    $date = request()->query('date', now()->toDateString());

    $data = cache()->get("missing_employees_summary_{$date}", []);

    $results = collect();

    foreach ($data as $item) {
        $shift = \App\Models\Shift::with(['zone.project'])->find($item['shift_id']);

        if (! $shift || ! $shift->zone || ! $shift->zone->project) {
            continue;
        }

        $employees = \App\Models\Employee::whereIn('id', $item['employee_ids'])
            ->where('status', 1)
            ->get([
                'id',
                'first_name',
                'father_name',
                'grandfather_name',
                'family_name',
                'mobile_number',
            ]);

        foreach ($employees as $employee) {
            $results->push([
                'employee_id' => $employee->id,
                'name' => "{$employee->name}",
                'mobile_number' => $employee->mobile_number,
                'project' => $shift->zone->project->name,
                'zone' => $shift->zone->name,
                'shift' => $shift->name,
            ]);
        }
    }

    return response()->json([
        'date' => $date,
        'count' => $results->count(),
        'missing_employees' => $results,
    ]);
});


Route::get('/uncovered-zones', function () {
    $date = request()->query('date', now()->toDateString());
    $data = cache()->get("missing_employees_summary_{$date}", []);
    
    $grouped = collect($data)->groupBy(fn ($item) => $item['zone_id']);

    $results = [];

    foreach ($grouped as $zoneId => $items) {
        $zone = \App\Models\Zone::with('project')->find($zoneId);
        if (! $zone || ! $zone->project) {
            continue;
        }

        $missing = collect($items)->sum(fn ($item) => count($item['employee_ids']));

        if ($missing > 0) {
            $results[] = [
                'project'  => $zone->project->name,
                'zone'     => $zone->name,
                'required' => $zone->emp_no,
                'missing'  => $missing,
            ];
        }
    }

    return response()->json([
        'date' => $date,
        'count' => count($results),
        'uncovered_zones' => $results,
    ]);
});





// ... (أي مسارات أخرى موجودة)

Route::post('/attendance-data', [ManulAttendanceController::class, 'getAttendanceData']);
Route::post('/attendance-coverage-status', [ManulAttendanceController::class, 'saveCoverageStatus']);

Route::get('/assignments-list', [ManulAttendanceController::class, 'assignmentsList']);
Route::post('/manual-attendance/record', [ManulAttendanceController::class, 'recordAttendance']);


