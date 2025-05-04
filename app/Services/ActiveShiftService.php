<?php

namespace App\Services;

use App\Models\Area;
use Carbon\Carbon;

class ActiveShiftService
{
    public function getActiveShiftsSummary(?Carbon $now = null): array
    {
        $now = $now ? $now->copy()->tz('Asia/Riyadh') : Carbon::now('Asia/Riyadh');

        return Area::with(['projects.zones.shifts' => function ($q) {
            $q->where('status', 1); // فقط الورديات النشطة
        }, 'projects.zones' => function ($q) {
            $q->where('status', 1); // فقط المواقع النشطة
        }, 'projects' => function ($q) {
            $q->where('status', 1); // فقط المشاريع النشطة
        }])->get()->map(function ($area) use ($now) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'projects' => $area->projects->map(function ($project) use ($now) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'emp_no' => $project->emp_no,
                        'zones' => $project->zones->map(function ($zone) use ($now) {
                            $activeShifts = [];
                            $currentShiftEmpNo = 0;

                            foreach ($zone->shifts as $shift) {
                                $isCurrent = $shift->isCurrentlyActiveV2($now);

                                $activeShifts[] = [
                                    'id' => $shift->id,
                                    'name' => $shift->name,
                                    'type' => $shift->type,
                                    'is_current_shift' => $isCurrent,
                                    'attendees_count' => $shift->attendances()
                                        ->whereDate('created_at', $now->toDateString())
                                        ->where('shift_id', $shift->id)
                                        ->where('zone_id', $zone->id)
                                        ->count(),
                                    'emp_no' => $shift->emp_no,
                                ];

                                if ($isCurrent) {
                                    $currentShiftEmpNo += $shift->emp_no;
                                }
                            }

                            return [
                                'id' => $zone->id,
                                'name' => $zone->name,
                                'emp_no' => $zone->emp_no,
                                'shifts' => $activeShifts,
                                'current_shift_emp_no' => $currentShiftEmpNo,
                                'active_coverages_count' => $zone->attendances()
                                    ->whereNull('shift_id')
                                    ->whereDate('created_at', $now->toDateString())
                                    ->count(),
                                'out_of_zone_count' => 0, // مخصص لاحقًا إذا عندك منطق خاص به
                            ];
                        }),
                    ];
                }),
            ];
        })->toArray();
    }
}
