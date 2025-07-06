<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// App\Http\Controllers\Api\EmployeeAttendanceController.php

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeAttendanceController extends Controller
{
    public function recent(Request $request)
    {
        $employeeId = Auth::user()->id;

        if (!$employeeId) {
            return response()->json([
                'status' => false,
                'message' => 'لم يتم ربط المستخدم بموظف'
            ], 403);
        }

        $query = Attendance::where('employee_id', $employeeId)
            ->whereDate('date', '>=', now()->subDays(1)->toDateString())
            ->orderByDesc('date');

        // فلترة حسب وقت التحديث (للتحديث الذكي)
        if ($request->has('last_synced_at')) {
            $query->where('updated_at', '>', $request->last_synced_at);
        }

       $attendances = $query->get([
    'id',
    'date',
    'check_in',
    'check_out',
    // 'check_in_datetime',
    // 'check_out_datetime',
    'status',
    // 'is_late',
    // 'is_coverage',
    // 'out_of_zone',
    // 'updated_at',
]);


        return response()->json([
            'status' => true,
            'data' => $attendances,
        ]);
    }
}
