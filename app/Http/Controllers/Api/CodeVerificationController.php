<?php

namespace App\Http\Controllers\Api;

use App\Models\Zone;
use App\Models\Attendance;
use App\Services\CodeDecoder;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyCodeRequest;

class CodeVerificationController extends Controller
{
    public function __construct(private readonly CodeDecoder $decoder) {}

    public function verify(VerifyCodeRequest $request): JsonResponse
    {
        try {
            $zoneId = $this->decoder->decode(
                $request->input('code'),
                $request->integer('employee_id')
            );

            $zone = Zone::query()
                ->with('project')      // إذا أردت تحميل المشروع
                ->findOrFail($zoneId);

            return response()->json([
                'success' => true,
                'data'    => [
                    'zone_id'      => $zone->id,
                    'zone_name'    => $zone->name,
                    'project_name' => $zone->project?->name,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function verifyWithAttendance(VerifyCodeRequest $req): JsonResponse
{
    // 1) فك الكود → $zoneId
    $zoneId = $this->decoder->decode(
        $req->input('code'),
        $req->integer('employee_id')
    );

    // 2) معلومات الموقع
    $zone = Zone::with('project')->findOrFail($zoneId);

    // 3) آخر حضور أو تغطية لليوم
    $last = Attendance::query()
        ->where('employee_id', $req->employee_id)
        ->whereDate('date', now('Asia/Riyadh'))
        ->latest('check_in_datetime')
        ->first();

    return response()->json([
        'success' => true,
        'data'    => [
            'zone_id'      => $zone->id,
            'zone_name'    => $zone->name,
            'project_name' => $zone->project?->name,
            'last_attendance' => $last
                ? [
                    'id'         => $last->id,
                    'status'     => $last->status,
                    'check_in'   => $last->check_in,
                    'check_out'  => $last->check_out,
                    'shift_id'   => $last->shift_id,
                    'shift_name' => $last->shift?->name,
                    'date'       => $last->date,
                  ]
                : null,
        ],
    ]);
}

}
