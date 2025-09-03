<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActiveShiftService;
use Illuminate\Http\JsonResponse;

class ActiveShiftController extends Controller
{
    protected ActiveShiftService $service;

    public function __construct(ActiveShiftService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $data = $this->service->getActiveShiftsSummary();

        return response()->json($data);
    }
    public function indexV2(): JsonResponse
    {
        $data = $this->service->getActiveShiftsSummaryV2();

        return response()->json($data);
    }
      public function indexV3(): JsonResponse
    {
        $data = $this->service->getActiveShiftsSummaryV3();

        return response()->json($data);
    }
}
