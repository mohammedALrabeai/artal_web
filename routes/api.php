<?php

use App\Http\Controllers\Api\AdminNotificationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OperationNotificationController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\SlideController;
use App\Http\Controllers\Api\ZoneController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\attendance\CoverageController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\EmployeeAuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeCoordinateController;
use App\Http\Controllers\EmployeeNotificationController;
use App\Http\Controllers\EmployeeStatusController;
use App\Http\Controllers\ProjectController;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Pusher\Pusher;

Route::post('/install-apk', [App\Http\Controllers\ApkController::class, 'installApk']);
Route::get('/download-apk/{filename}', [App\Http\Controllers\ApkController::class, 'downloadApk']);

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
    Route::post('employee/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('employee/attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::post('employee/attendance/sync-check-in', [AttendanceController::class, 'syncCheckIn']);
    Route::post('employee/attendance/sync-check-out', [AttendanceController::class, 'syncCheckOut']);
    Route::get('employee/attendance', [AttendanceController::class, 'index']);
    Route::get('attendances/filter', [AttendanceController::class, 'filter']);
    // Route::post('attendances/coverage', [AttendanceController::class, 'store']);
    Route::post('/attendances/coverage/check-in', [AttendanceController::class, 'checkInCoverage']);
    Route::post('/attendances/coverage/check-out', [AttendanceController::class, 'checkOutCoverage']);

    Route::post('/zones/nearby', [ZoneController::class, 'nearbyZones']);
    Route::post('/zones/nearby-with-shift', [ZoneController::class, 'nearbyZonesWithCurrentShifts']);

    Route::post('/employees/update-zone-status', [EmployeeCoordinateController::class, 'updateZoneStatus']);

});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/employee-route/{employeeId}', [EmployeeCoordinateController::class, 'getEmployeeRoute']);
    Route::get('/employee-locations', [EmployeeCoordinateController::class, 'getRecentEmployeeLocations']);
    Route::get('/employee-locations2', [EmployeeCoordinateController::class, 'getActiveAndInactiveEmployees']);
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

Route::get('/areas-with-details', [AreaController::class, 'getAreasWithDetails2']);
Route::get('/areas-with-details2', [AreaController::class, 'getAreasWithDetails3']);
Route::get('/assigned-employees', [AreaController::class, 'getAssignedEmployeesForShifts']);
Route::get('/attendance', [AttendanceController::class, 'getAttendanceStatus']);

Route::get('/slides', [SlideController::class, 'getActiveSlides']);

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
Route::middleware('auth:sanctum')->
group(function () {
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
// });
