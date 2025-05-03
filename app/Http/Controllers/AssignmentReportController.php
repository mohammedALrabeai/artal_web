<?php

// app/Http/Controllers/AssignmentReportController.php

namespace App\Http\Controllers;

use App\Models\Employee;

class AssignmentReportController extends Controller
{
    public function mobileNumbers()
    {
        $employees = Employee::whereHas('currentZone')
            ->whereNotNull('mobile_number')
            ->pluck('mobile_number')
            ->unique()
            ->values();

        $output = $employees->implode("\n");
        $output .= "\n".$employees->count();

        return response($output, 200)
            ->header('Content-Type', 'text/plain');
    }
}
