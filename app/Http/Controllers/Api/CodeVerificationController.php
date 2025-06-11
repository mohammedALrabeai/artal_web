<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyCodeRequest;
use App\Models\Zone;
use App\Services\CodeDecoder;
use Illuminate\Http\JsonResponse;

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
}
