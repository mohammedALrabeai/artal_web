<?php

use App\Models\EmployeeCoordinate;

use App\Filament\Pages\EmployeeMap;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeMapController;
use App\Http\Controllers\FileUploadController2;
use Illuminate\Http\Request; 

Route::get('/', function () {
    return view('welcome');
});




use App\Filament\Pages\EmployeePaths;


Route::get('/generate-pdf', [App\Http\Controllers\PdfController::class, 'generatePdf']);

use App\Models\Employee;
use App\Services\EmployeePdfService;

use App\Models\EmployeeProjectRecord;


Route::get('/employee-project-record/{id}/pdf', function ($id) {
    $record = EmployeeProjectRecord::findOrFail($id);

    // إنشاء كائن الخدمة وتوليد PDF
    $service = new EmployeePdfService();
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








Route::get('/upload', [FileUploadController2::class, 'showForm'])->name('upload.form');
Route::post('/upload', [FileUploadController2::class, 'uploadFile'])->name('upload.file');


use App\Http\Controllers\S3TestController;

Route::get('/test-s3', [S3TestController::class, 'testS3']);

    
    
    
    

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
