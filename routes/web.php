<?php

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\EmployeeCoordinate;
use App\Services\EmployeePdfService;
use Maatwebsite\Excel\Facades\Excel;
use App\Filament\Pages\EmployeePaths;
use App\Models\EmployeeProjectRecord;
use Illuminate\Support\Facades\Route;
use App\Exports\EmployeeChangesExport;
use App\Exports\WorkPatternPayrollExport;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\S3TestController;
use App\Services\ProjectEmployeesPdfService;
use App\Exports\SelectedProjectsEmployeeExport;
use App\Http\Controllers\FileUploadController2;
use App\Http\Controllers\SlotTimelineController;
use App\Exports\CombinedAttendanceWorkPatternExport;
use App\Http\Controllers\attendance\AttendanceExport2Controller;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ExportWorkPatternPayrollJob;
use Illuminate\Support\Facades\Auth;



use App\Http\Controllers\attendance\AttendanceYearlyExportController;
use App\Http\Controllers\attendance\ImprovedAttendanceExport2Controller;

  use App\Models\Shift;

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
Route::get('/export-enhanced-attendance', [ImprovedAttendanceExport2Controller::class, 'export2'])->name('export.enhanced.attendance2');

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
    $service->generate($records, 'جدول التشغيل', $startDate, $ids); // ← تمرير العنوان كنص، وتمرير IDs
})->name('projects.export.pdf')->middleware(['web', 'auth']);

Route::get('/admin/timeline', fn() => view('admin.timeline'))->middleware('auth');
Route::get('/timeline-slots', [App\Http\Controllers\TimelineController::class, 'slots'])->name('timeline.slots');
Route::get('/admin/timeline-demo/{project}', [\App\Http\Controllers\TimelineController::class, 'show'])
    ->name('timeline-demo');

    Route::get('/slot-timeline', [SlotTimelineController::class, 'index'])->name('slot.timeline');







Route::post('/exports/employee-changes', function () {
    $from = request()->input('from');
    $to = request()->input('to');

    if (! $from || ! $to || $from > $to) {
        abort(400, 'تأكد من صحة التواريخ');
    }

    $fileName = 'المتغيرات_' . $from . '_حتى_' . $to . '.xlsx';

    return Excel::download(new EmployeeChangesExport($from, $to), $fileName);
})->name('exports.employee-changes')->middleware(['auth']);




Route::post('/exports/work-schedule', function () {
    $projectIds = request()->input('projects', []);
    $startDate = request()->input('start_date');

    // ✅ إذا لم يتم اختيار شيء
    if (empty($projectIds)) {
        return back()->withErrors(['projects' => 'يرجى اختيار مشروع واحد على الأقل.']);
    }

    // ✅ إذا اختار "جميع المشاريع"
    if (in_array('all', $projectIds)) {
        $projectIds = \App\Models\Project::where('status', true)->pluck('id')->toArray();
    }

    // تحقق من التاريخ
    if (! $startDate) {
        return back()->withErrors(['start_date' => 'يرجى تحديد تاريخ البداية.']);
    }

    return Excel::download(
        new SelectedProjectsEmployeeExport($projectIds, onlyActive: true, startDate: $startDate),
        'جداول_التشغيل.xlsx'
    );
})->name('exports.work-schedule')->middleware(['auth']);



// في routes/web.php



Route::post('/exports/work-pattern-payroll', function (Request $request) {
    $projectIds = \App\Models\Project::where('status', true)->pluck('id')->toArray();
    $currentDate = now()->format('Y-m-d'); // يمكنك تمرير تاريخ محدد إذا أردت

    // هذا هو الجزء الذي يرسل المهمة إلى قائمة الانتظار
    ExportWorkPatternPayrollJob::dispatch($projectIds, $currentDate, Auth::id());

    // إعادة توجيه المستخدم مع رسالة نجاح فورية
    return back()->with('success', 'تم إرسال طلب التقرير بنجاح. سيتم إعلامك عند اكتمال التصدير.');
})->name('exports.work-pattern-payroll')->middleware(['auth']);



Route::get("/downloads/reports/{fileName}", function ($fileName) {
    $path = Storage::disk("public")->path("exports/{$fileName}");

    if (!Storage::disk("public")->exists("exports/{$fileName}")) {
        abort(404, "الملف غير موجود.");
    }

    return response()->download($path, $fileName);
})->name("downloads.report")->middleware(["auth"]);

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
