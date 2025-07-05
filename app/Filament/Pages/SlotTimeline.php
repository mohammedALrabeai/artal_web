<?php

namespace App\Filament\Pages;

use App\Models\Zone;
use App\Models\Project;
use Filament\Pages\Page;
use Mockery\Matcher\Not;
use App\Models\Attendance;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use App\Models\EmployeeProjectRecord;
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class SlotTimeline extends Page
{
    use HasPageShield;
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static string $view = 'filament.pages.slot-timeline';
    protected static ?string $navigationLabel = 'المخطط الزمني للشواغر';
    protected static ?string $title = 'المخطط الزمني للشواغر';
    protected static ?int $navigationSort = 90;

    public function getViewData(): array
    {
        $projectId = request('project_id');
       $fromDate = request('from')
        ? Carbon::parse(request('from'))->startOfDay()
        : now()->startOfMonth()->startOfDay();

    $toDate = request('to')
        ? Carbon::parse(request('to'))->endOfDay()
        : now()->endOfMonth()->endOfDay();

    /**
     * ───────────────────────────────────────────────────────────
     * 💡  قيد -30- يوماً
     * ───────────────────────────────────────────────────────────
     */
    if ($fromDate->diffInDays($toDate) > 31) {
        // ضبط النهاية تلقائياً على 30 يوماً من البداية
        $toDate = $fromDate->copy()->addDays(30)->endOfDay();

        // إشعار للمستخدم (warning أو danger كما تفضّل)
       Notification::make()
            ->title('الفترة المحددة طويلة جداً')
            ->body('تم ضبط نهاية الفترة تلقائياً على 30 يوماً من البداية.')
            ->warning()
            ->send();
    }

    // نعيد المتغيرين كـ string لاستعمالهما في Blade والاستعلامات
    $from = $fromDate->toDateString();
    $to   = $toDate->toDateString();

        $projects = Project::all();
        $days = collect();
        $data = [];

        if ($projectId) {
            $period = Carbon::parse($from)->toPeriod($to);
            foreach ($period as $date) {
                $days->push($date->toDateString());
            }

            $zones = Zone::with(['shifts.slots'])->where('project_id', $projectId)->get();

            // تحميل الموظفين المسندين حسب الشفت سلوت خلال الفترة
            $assignments = EmployeeProjectRecord::with(['employee', 'shiftSlot'])
                ->where('project_id', $projectId)
                ->where('status', true)
                ->whereDate('start_date', '<=', $to)
                ->where(function ($q) use ($from) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $from);
                })
                ->get();

            // جلب بيانات الحضور العادي للفترة المحددة
            $attendances = Attendance::with(['zone'])
                ->select('employee_id', 'zone_id', 'shift_id', 'check_in', 'check_out', 'date', 'is_coverage')
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to)
                ->where('is_coverage', false) // الحضور العادي فقط
                ->get()
                ->groupBy(fn ($a) => $a->employee_id . '_' . $a->date);

            // جلب بيانات التغطيات للفترة المحددة
            $coverages = Attendance::with(['zone'])
                ->select('employee_id', 'zone_id', 'shift_id', 'check_in', 'check_out', 'date', 'is_coverage')
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to)
                ->where('is_coverage', true) // التغطيات فقط
                ->get()
                ->groupBy(fn ($c) => $c->employee_id . '_' . $c->date);

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

                    foreach ($shift->slots as $slot) {
                        $slotData = [
                            'slot_number' => $slot->slot_number,
                            'days' => [],
                        ];

                        foreach ($days as $day) {
                            $pattern = $shift->getWorkPatternForDate($day);

                            $employee = $assignments
                                ->first(fn ($rec) =>
                                    $rec->zone_id === $zone->id &&
                                    $rec->shift_id === $shift->id &&
                                    $rec->shift_slot_id === $slot->id &&
                                    $rec->start_date <= $day &&
                                    (is_null($rec->end_date) || $rec->end_date >= $day)
                                );

                            // البحث عن بيانات الحضور العادي للموظف في هذا اليوم
                            $attendance = null;
                            if ($employee) {
                                $attendanceKey = $employee->employee->id . '_' . $day;
                                $attendanceRecords = $attendances->get($attendanceKey);
                                
                                if ($attendanceRecords) {
                                    // البحث عن سجل الحضور المطابق للمنطقة والشفت
                                    $attendance = $attendanceRecords->first(function ($record) use ($zone, $shift) {
                                        return $record->zone_id == $zone->id && $record->shift_id == $shift->id;
                                    });
                                }
                            }

                            // البحث عن بيانات التغطيات للموظف في هذا اليوم
                            $employeeCoverages = [];
                            if ($employee) {
                                $coverageKey = $employee->employee->id . '_' . $day;
                                $coverageRecords = $coverages->get($coverageKey);
                                
                                if ($coverageRecords) {
                                    foreach ($coverageRecords as $coverage) {
                                        $employeeCoverages[] = [
                                            'zone_name' => $coverage->zone->name ?? 'غير محدد',
                                            'check_in' => $coverage->check_in,
                                            'check_out' => $coverage->check_out,
                                            'check_in_time' => $coverage->check_in ? Carbon::parse($coverage->check_in)->format('H:i') : null,
                                            'check_out_time' => $coverage->check_out ? Carbon::parse($coverage->check_out)->format('H:i') : null,
                                        ];
                                    }
                                }
                            }

                            $slotData['days'][] = [
                                'date' => $day,
                                'is_working_day' => $pattern === 'working',
                                'employee' => $employee ? [
                                    'id' => $employee->employee->id,
                                    'name' => $employee->employee->name ?? '—',
                                    'start_date' => $employee->start_date,
                                    'end_date' => $employee->end_date,
                                ] : null,
                                'attendance' => $attendance ? [
                                    'check_in' => $attendance->check_in,
                                    'check_out' => $attendance->check_out,
                                    'check_in_time' => $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : null,
                                    'check_out_time' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : null,
                                ] : null,
                                'coverages' => $employeeCoverages, // قائمة التغطيات
                            ];
                        }

                        $shiftData['slots'][] = $slotData;
                    }

                    $zoneData['shifts'][] = $shiftData;
                }

                $data[] = $zoneData;
            }
        }

        return compact('projects', 'projectId', 'from', 'to', 'days', 'data');
    }
}

