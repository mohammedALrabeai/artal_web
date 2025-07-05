<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Models\EmployeeProjectRecord;
use App\Services\ProjectEmployeesPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function exportAttendance(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return Excel::download(new AttendanceExport($startDate, $endDate), 'attendance_report.xlsx');
    }
     public function exportEnhancedAttendance(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return Excel::download(new AttendanceExport($startDate, $endDate), 'attendance_report.xlsx');
    }

    public function export(Request $request)
    {
        // التحقق من المعطيات
        $request->validate([
            'ids' => 'required|string',
            'start_date' => 'required|date',
        ]);

        $projectIds = explode(',', $request->input('ids'));
        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();

        // جلب السجلات مع العلاقات المطلوبة
        $records = EmployeeProjectRecord::with(['employee', 'project', 'zone.pattern', 'shift'])
            ->whereIn('project_id', $projectIds)
            ->where('status', true)
            ->get();

        // تمرير السجلات وتاريخ البداية إلى خدمة PDF
        return app(ProjectEmployeesPdfService::class)->generate($records, 'تقرير نمط العمل - الموظفين', $startDate);
    }
}
