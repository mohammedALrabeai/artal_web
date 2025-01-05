<?php

namespace App\Http\Controllers;

use App\Exports\ProjectsZonesReportExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function projectsZonesReportForm()
    {
        return view('reports.projects_zones_form'); // نموذج لاختيار التواريخ
    }

    public function exportProjectsZonesReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
    
        return Excel::download(
            new ProjectsZonesReportExport($request->start_date, $request->end_date),
            'projects_zones_report.xlsx'
        );
    }
    
}
