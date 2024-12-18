<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeeCoordinate;

class EmployeeCoordinateController extends Controller
{
    /**
     * تخزين الإحداثيات القادمة من التطبيق.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'timestamp' => 'required|date',
            'status' => 'required|in:inside,outside',
            'shift_id' => 'nullable|exists:shifts,id',
            'zone_id' => 'nullable|exists:zones,id',
            'distance' => 'nullable|numeric|min:0',
        ]);

        $coordinate = EmployeeCoordinate::create($validated);

        return response()->json([
            'success' => true,
            'data' => $coordinate,
        ], 201);
    }

    /**
     * عرض جميع الإحداثيات.
     */
    public function index()
    {
        $coordinates = EmployeeCoordinate::with(['employee', 'shift', 'zone'])->get();

        return response()->json([
            'success' => true,
            'data' => $coordinates,
        ]);
    }
}
