<?php

use App\Filament\Pages\EmployeePaths;
use App\Http\Controllers\attendance\AttendanceExport2Controller;
use App\Http\Controllers\attendance\AttendanceYearlyExportController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FileUploadController2;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\S3TestController;
use App\Models\Employee;
use App\Models\EmployeeCoordinate;
use App\Models\EmployeeProjectRecord;
use App\Services\EmployeePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generate-pdf', [App\Http\Controllers\PdfController::class, 'generatePdf']);

Route::get('/employee-project-record/{id}/pdf', function ($id) {
    $record = EmployeeProjectRecord::findOrFail($id);

    // إنشاء كائن الخدمة وتوليد PDF
    $service = new EmployeePdfService;
    $service->generatePdf($record);
})->name('employee_project_record.pdf');

Route::get('/filament/employee-paths/{employeeId}', EmployeePaths::class)
    ->name('filament.pages.employee-paths');

// Route::get('/filament/employee-route/{employeeId}', [EmployeePaths::class, 'getEmployeeRoute']);

Route::get('/filament/employee-route/{employeeId}', function (Request $request, $employeeId) {
    $date = $request->get('date', now()->toDateString());

    try {
        // جلب بيانات المسار
        $coordinates = \App\Models\EmployeeCoordinate::where('employee_id', $employeeId)
            ->whereDate('timestamp', $date)
            ->orderBy('timestamp', 'asc')
            ->get(['latitude', 'longitude', 'timestamp', 'zone_id']);

        // جلب بيانات المنطقة بناءً على أول `zone_id` غير null
        $zoneId = $coordinates->whereNotNull('zone_id')->first()?->zone_id;

        $zone = $zoneId ? \App\Models\Zone::find($zoneId, ['lat', 'longg', 'area']) : null;

        return response()->json([
            'route' => $coordinates,
            'zone' => $zone,
        ]);
    } catch (\Exception $e) {
        // تسجيل الخطأ وإرجاع استجابة واضحة
        \Log::error('Error fetching route data', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json(['error' => 'Internal Server Error'], 500);
    }
});

Route::get('/export/attendance', [ExportController::class, 'exportAttendance'])->name('export.attendance');

Route::get('/export-attendance', [AttendanceExport2Controller::class, 'export2'])->name('export.attendance2');

Route::get('/export-attendance-filtered', [AttendanceExport2Controller::class, 'exportFiltered'])
    ->name('export.attendance.filtered')
    ->middleware('signed'); // ✅ تأمين الطلبات الموقعة
Route::get('/export-attendance-yearly', [AttendanceYearlyExportController::class, 'exportYearly'])
    ->name('export.attendance.yearly')
    ->middleware('signed');

Route::get('/export-projects-zones-report', [ReportController::class, 'exportProjectsZonesReport'])
    ->name('export.projects.zones.report')
    ->middleware('signed');

Route::get('/upload', [FileUploadController2::class, 'showForm'])->name('upload.form');
Route::post('/upload', [FileUploadController2::class, 'uploadFile'])->name('upload.file');

Route::get('/test-s3', [S3TestController::class, 'testS3']);

Route::get('/admin/projects/export/pdf', function () {
    $ids = session('export_pdf_ids');
    $startDate = session('export_pdf_start_date');

    if (! $ids || ! $startDate) {
        abort(403, 'بيانات التصدير غير متوفرة');
    }

    $records = \App\Models\EmployeeProjectRecord::with(['employee', 'project', 'zone.pattern', 'shift'])
        ->whereIn('project_id', $ids)
        ->where('status', true)
        ->get();

    $service = new \App\Services\ProjectEmployeesPdfService;
    $service->generate($records, 'تقرير نمط العمل - الموظفين', $startDate);
})->name('projects.export.pdf')->middleware(['web', 'auth']);

// Route::get('/filament/employee-route/{employeeId}', function ($employeeId) {
//     $coordinates = EmployeeCoordinate::where('employee_id', $employeeId)
//         ->orderBy('timestamp', 'asc')
//         ->get(['latitude', 'longitude']);

//     $geoJson = [
//         'type' => 'FeatureCollection',
//         'features' => $coordinates->map(function ($coordinate) {
//             return [
//                 'type' => 'Feature',
//                 'geometry' => [
//                     'type' => 'Point',
//                     'coordinates' => [$coordinate->longitude, $coordinate->latitude],
//                 ],
//             ];
//         }),
//     ];

//     return response()->json($geoJson);
// });
