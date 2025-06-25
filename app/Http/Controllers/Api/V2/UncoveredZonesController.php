<?php

namespace App\Http\Controllers\Api\V2;

use Carbon\Carbon;
use App\Models\Zone;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\EmployeeStatus;
use App\Http\Controllers\Controller;
use App\Models\Area;


class UncoveredZonesController extends Controller
{



public function uncoveredZones(Request $request)
{
    $now = now('Asia/Riyadh');

    $data = [];

    Area::with(['projects.zones' => function ($q) {
        $q->where('status', 1);
    }, 'projects' => function ($q) {
        $q->where('status', 1);
    }])->get()->each(function ($area) use (&$data, $now) {
        foreach ($area->projects as $project) {
            foreach ($project->zones as $zone) {
                $required = $zone->emp_no;

                $presentCount = $zone->attendances()
                    ->where('status', 'present')
                    ->whereNull('check_out')
                    ->whereDate('date', $now->toDateString())
                    ->count();

                $coverageCount = $zone->attendances()
                    ->where('status', 'coverage')
                    ->whereNull('check_out')
                    ->whereDate('date', $now->toDateString())
                    ->count();

                $actual = $presentCount + $coverageCount;
                $missing = $required - $actual;

                if ($missing > 0) {
                    $data[] = [
                        'project'  => $project->name,
                        'zone'     => $zone->name,
                        'required' => $required,
                        'missing'  => $missing,
                    ];
                }
            }
        }
    });

    return response()->json([
        'status' => true,
        'uncovered_zones' => $data,
    ]);
}




}