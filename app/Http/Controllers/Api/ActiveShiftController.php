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
}
