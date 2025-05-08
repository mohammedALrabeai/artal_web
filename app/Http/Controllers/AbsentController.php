<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AbsentController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * إرجاع الموظفين الغائبين فعليًا اليوم.
     */
    public function getTrulyAbsentEmployees(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        $records = $this->attendanceService->getTrulyAbsentEmployees($date);

        $data = $records->map(function ($attendance) {
            return [
                'employee_id' => $attendance->employee_id,
                'name' => $attendance->employee->name,
                'mobile_number' => $attendance->employee->mobile_number,
                'project' => optional($attendance->employee->currentProjectRecord)->project->name ?? null,
                'zone' => optional($attendance->zone)->name,
                'shift' => optional($attendance->shift)->name,
            ];
        });

        return response()->json([
            'date' => $date,
            'absent_employees_count' => $data->count(),
            'absent_employees' => $data,
        ]);
    }
}
