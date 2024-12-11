<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Area;
use App\Models\Shift;
use Carbon\Carbon;

class AreaController extends Controller
{
    public function getAreasWithDetails()
    {
        $currentTime = Carbon::now();

        $areas = Area::with(['projects.zones.shifts.attendances'])->get();

        $data = $areas->map(function ($area) use ($currentTime) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'projects' => $area->projects->map(function ($project) use ($currentTime) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'zones' => $project->zones->map(function ($zone) use ($currentTime) {
                            // الشفت الحالي
                            $currentShift = $zone->shifts->filter(function ($shift) use ($currentTime) {
                                $morningStart = Carbon::createFromTimeString($shift->morning_start);
                                $morningEnd = Carbon::createFromTimeString($shift->morning_end);
                                $eveningStart = Carbon::createFromTimeString($shift->evening_start);
                                $eveningEnd = Carbon::createFromTimeString($shift->evening_end);

                                return ($currentTime->between($morningStart, $morningEnd) || $currentTime->between($eveningStart, $eveningEnd));
                            })->first();

                            // عدد الحاضرين في الشفت الحالي
                            $attendanceCount = $currentShift
                                ? $currentShift->attendances->where('status', 'present')->count()
                                : 0;

                            return [
                                'id' => $zone->id,
                                'name' => $zone->name,
                                'current_shift' => $currentShift ? [
                                    'id' => $currentShift->id,
                                    'name' => $currentShift->name,
                                    'type' => $currentShift->morning_start ? 'morning' : 'evening',
                                    'attendees_count' => $attendanceCount,
                                ] : null,
                            ];
                        }),
                    ];
                }),
            ];
        });

        return response()->json($data);
    }


    public function getAreasWithDetails2()
    {
        $currentTime = Carbon::now();

        $areas = Area::with(['projects.zones.shifts.attendances'])->get();

        $data = $areas->map(function ($area) use ($currentTime) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'projects' => $area->projects->map(function ($project) use ($currentTime) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'zones' => $project->zones->map(function ($zone) use ($currentTime) {
                            $shifts = $zone->shifts->map(function ($shift) use ($currentTime) {
                                $isCurrentShift = $this->isCurrentShift($shift, $currentTime);

                                // $attendanceCount = $shift->attendances
                                //     ->where('status', 'present')
                                //     ->count();
                                $attendanceCount = $shift->attendances
    ->where('status', 'present')
    ->where('date', Carbon::today()->toDateString())
    ->count();

                                return [
                                    'id' => $shift->id,
                                    'name' => $shift->name,
                                    'type' => $shift->morning_start ? 'morning' : 'evening',
                                    'is_current_shift' => $isCurrentShift,
                                    'attendees_count' => $attendanceCount,
                                    'emp_no' => $shift->emp_no,
                                ];
                            });

                            $currentShift = $shifts->where('is_current_shift', true)->first();

                            return [
                                'id' => $zone->id,
                                'name' => $zone->name,
                                'emp_no' => $zone->emp_no,
                                'shifts' => $shifts,
                                'current_shift_emp_no' => $currentShift ? $currentShift['emp_no'] : 0,
                            ];
                        }),
                    ];
                }),
            ];
        });

        return response()->json($data);
    }

    private function isCurrentShift($shift, $currentTime)
    {
        $morningStart = Carbon::createFromTimeString($shift->morning_start);
        $morningEnd = Carbon::createFromTimeString($shift->morning_end);
        $eveningStart = Carbon::createFromTimeString($shift->evening_start);
        $eveningEnd = Carbon::createFromTimeString($shift->evening_end);

        return $currentTime->between($morningStart, $morningEnd) || $currentTime->between($eveningStart, $eveningEnd);
    }


}
