<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\EmployeeAuthController;

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\Api\SlideController;









Route::post('/employee/login', [EmployeeAuthController::class, 'login']);
Route::post('/employee/verify-otp', [EmployeeAuthController::class, 'verifyOtp']);
Route::post('employee/check-device-approval', [App\Http\Controllers\Auth\EmployeeAuthController::class, 'checkDeviceApproval']);
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






Route::middleware('auth:employee')->group(function () {
    Route::get('employee/schedule', [EmployeeController::class, 'schedule']);
});












Route::middleware('auth:employee')->group(function () {
    Route::post('employee/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('employee/attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::post('employee/attendance/sync-check-in', [AttendanceController::class, 'syncCheckIn']);
    Route::post('employee/attendance/sync-check-out', [AttendanceController::class, 'syncCheckOut']);
    Route::get('employee/attendance', [AttendanceController::class, 'index']);
    Route::get('attendances/filter', [AttendanceController::class, 'filter']);

});
// Route::middleware('auth:employee')->group(function () {
//     Route::post('/employee/check-in', [AttendanceController::class, 'checkIn']);
//     Route::post('/employee/check-out', [AttendanceController::class, 'checkOut']);
// });

Route::middleware('auth:employee')->group(function () {
    Route::post('/employee/attendance', [AttendanceController::class, 'markAttendance']);
});










Route::get('/areas-with-details', [AreaController::class, 'getAreasWithDetails2']);



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



















Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function (Request $request) {   
    return response()->json(
        [
            'status' => 'success',
            'message' => 'test'
        ]   
    );
});
