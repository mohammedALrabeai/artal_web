<?php
// app/Http/Controllers/AttendanceRenewalController.php
namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRenewalRequest;
use App\Services\Attendance\RenewalService;
use Illuminate\Http\JsonResponse;

class AttendanceRenewalController extends Controller
{
    public function store(StoreAttendanceRenewalRequest $request, ?int $attendanceId = null): JsonResponse
    {
        $attendanceId = $attendanceId ?? (int) $request->input('attendance_id');
        $result = app(RenewalService::class)->create(
            attendanceId: $attendanceId,
            kind:   $request->input('kind', 'manual'),
            status: $request->input('status', 'ok'),
            payload:$request->input('payload')
        );

        return response()->json($result);
    }
}
