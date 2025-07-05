<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Zone;
use App\Models\Shift;
use App\Models\ShiftSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SlotTimelineController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::all();

        $projectId = $request->input('project_id');
        $from = $request->input('from') ?? now()->startOfMonth()->toDateString();
        $to = $request->input('to') ?? now()->endOfMonth()->toDateString();

        $days = collect();
        $period = Carbon::parse($from)->toPeriod($to);
        foreach ($period as $date) {
            $days->push($date->toDateString());
        }

        $data = [];

        if ($projectId) {
            $zones = Zone::where('project_id', $projectId)->get();

            foreach ($zones as $zone) {
                $zoneData = [
                    'zone' => $zone,
                    'shifts' => [],
                ];

                foreach ($zone->shifts as $shift) {
                    $shiftData = [
                        'shift' => $shift,
                        'slots' => [],
                    ];

                    for ($i = 1; $i <= $shift->emp_no; $i++) {
                        $slotData = [
                            'slot_number' => $i,
                            'days' => [],
                        ];

                        foreach ($days as $day) {
                            $pattern = $shift->getWorkPatternForDate($day); // ترجع يوم عمل أو راحة
                            $slotData['days'][] = [
                                'date' => $day,
                                'is_working_day' => $pattern === 'working', // حسب اللوجيك لديك
                            ];
                        }

                        $shiftData['slots'][] = $slotData;
                    }

                    $zoneData['shifts'][] = $shiftData;
                }

                $data[] = $zoneData;
            }
        }

        return view('slot-timeline.index', compact('projects', 'data', 'projectId', 'from', 'to', 'days'));
    }
}
