<?php 
namespace App\Http\Controllers;
use App\Models\Zone;
use App\Models\Shift;
use App\Models\Project;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\EmployeeCoordinate;
use App\Models\EmployeeProjectRecord;
use App\Services\EmployeePdfService;    
use App\Services\ProjectEmployeesPdfService;   
use Carbon\Carbon;

use App\Models\ShiftSlot;




class TimelineController extends Controller
{

public function slots(Request $request)
{
    $projectId = $request->integer('project_id');
    $startDate = Carbon::parse($request->input('start_date'));
    $endDate = Carbon::parse($request->input('end_date'));

    $project = Project::findOrFail($projectId);

    $zones = Zone::where('project_id', $projectId)
        ->with(['shifts.slots'])
        ->get();

    $days = [];
    for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
        $days[] = $date->format('Y-m-d');
    }

    $result = [
        'project' => $project->name,
        'zones' => [],
        'days' => $days, // useful for headers
    ];

    foreach ($zones as $zone) {
        $zoneData = [
            'id' => $zone->id,
            'name' => $zone->name,
            'shifts' => [],
        ];

        foreach ($zone->shifts as $shift) {
            $shiftData = [
                'id' => $shift->id,
                'name' => $shift->name,
                'slots' => [],
            ];

            foreach ($shift->slots as $slot) {
                // كل سلوت في الوردية
                $slotData = [
                    'id' => $slot->id,
                    'slot_number' => $slot->slot_number,
                    'days' => [],
                ];

                foreach ($days as $date) {
                    // جلب اسناد الموظف في اليوم الحالي
                    $record = EmployeeProjectRecord::where('shift_slot_id', $slot->id)
                        ->where('status', true)
                        ->where('start_date', '<=', $date)
                        ->where(function ($q) use ($date) {
                            $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
                        })
                        ->with('employee')
                        ->first();

                    $employee = $record?->employee;

                    // جلب تحضير الموظف
                    $attendance = $employee
                        ? Attendance::where('employee_id', $employee->id)
                            ->where('date', $date)
                            ->where('shift_id', $shift->id)
                            ->first()
                        : null;

                    $slotData['days'][] = [
                        'date' => $date,
                        'employee_id' => $employee?->id,
                        'employee_name' => $employee
                            ? "{$employee->first_name} {$employee->father_name} {$employee->family_name}"
                            : null,
                        'attendance' => $attendance
                            ? [
                                'check_in' => $attendance->check_in_time,
                                'check_out' => $attendance->check_out_time,
                            ]
                            : null,
                        'is_empty' => !$employee,
                    ];
                }
                $shiftData['slots'][] = $slotData;
            }

            $zoneData['shifts'][] = $shiftData;
        }
        $result['zones'][] = $zoneData;
    }

    return response()->json($result);
}


public function show($projectId)
    {
        // 1) إعداد الفترة (أنت ترسلها بالطلب أو ثابت حالياً)
        $from = request('from', now()->startOfMonth()->toDateString());
        $to = request('to', now()->endOfMonth()->toDateString());

        // 2) جلب المشروع مع العلاقات (zones > shifts > slots)
        $project = Project::with([
            'zones.shifts.slots'
        ])->findOrFail($projectId);

        // 3) جميع slot_ids في هذا المشروع
        $slotIds = $project->zones
            ->flatMap(fn($zone) => $zone->shifts)
            ->flatMap(fn($shift) => $shift->slots)
            ->pluck('id')
            ->unique()
            ->values()
            ->toArray();

        // 4) كل الإسنادات المرتبطة بأي سلوت في الفترة
        $records = EmployeeProjectRecord::with('employee')
            ->whereIn('shift_slot_id', $slotIds)
            ->where(function($q) use ($from) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $from);
            })
            ->where('start_date', '<=', $to)
            ->get();

        // 5) جمع كل الموظفين المرتبطين بهذه الاسنادات
        $employeeIds = $records->pluck('employee_id')->unique()->values()->toArray();

        // 6) جلب كل الحضور والانصرافات اليومية للموظفين المعنيين
        $attendances = Attendance::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$from, $to])
            ->get()
            ->groupBy(fn($a) => $a->employee_id.'-'.$a->date);

        // 7) تجهيز مصفوفة البيانات للعرض
        $data = [];
        foreach ($project->zones as $zone) {
            foreach ($zone->shifts as $shift) {
                foreach ($shift->slots as $slot) {
                    $slotKey = "zone_{$zone->id}_shift_{$shift->id}_slot_{$slot->id}";
                    $data[$slotKey] = [
                        'zone' => $zone,
                        'shift' => $shift,
                        'slot' => $slot,
                        'days' => [],
                    ];

                    // لكل يوم
                    $start = Carbon::parse($from);
                    $end = Carbon::parse($to);
                    $period = \Carbon\CarbonPeriod::create($start, $end);

                    foreach ($period as $date) {
                        $currentDate = $date->toDateString();
                        // ابحث عن الموظف المسند في هذا اليوم لهذا السلوت
                        $record = $records->first(function ($rec) use ($slot, $currentDate) {
                            return $rec->shift_slot_id == $slot->id
                                && $rec->start_date <= $currentDate
                                && (
                                    is_null($rec->end_date)
                                    || $rec->end_date >= $currentDate
                                );
                        });

                        $employee = $record?->employee;
                        $employeeId = $employee?->id;

                        // ابحث عن حضور اليوم
                        $attendance = $employeeId
                            ? $attendances->get($employeeId.'-'.$currentDate)?->first()
                            : null;

                        $data[$slotKey]['days'][] = [
                            'date' => $currentDate,
                            'employee' => $employee,
                            'record' => $record,
                            'attendance' => $attendance,
                            // أضف ما تريد من تفاصيل
                        ];
                    }
                }
            }
        }

        // 8) أرسل للعرض
        return view('timeline-demo', [
            'project' => $project,
            'data' => $data,
            'from' => $from,
            'to' => $to,
        ]);
    }

    }