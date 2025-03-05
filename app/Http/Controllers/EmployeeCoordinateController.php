<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use App\Models\Shift;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\EmployeeCoordinate;
use Illuminate\Support\Facades\DB;
use App\Models\EmployeeProjectRecord;

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

        $employee = Employee::find($validated['employee_id']);
        if ($employee) {
            $employee->update([
                'out_of_zone' => $validated['status'] === 'outside',
            ]);
        }

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

    public function updateZoneStatus(Request $request)
    {
        $validated = $request->validate([
            'out_of_zone' => 'required|boolean',
        ]);

        //  $employee = Employee::find($request->user()->id);
        $employee = $request->user();
        $employee->update(['out_of_zone' => $validated['out_of_zone']]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الموظف بنجاح.',
            'data' => $employee,
        ]);
    }

    /**
     * ✅ جلب مسار الموظف ليوم معين
     */
    public function getEmployeeRoute(Request $request, $employeeId)
    {
        // ✅ التحقق من صحة المدخلات
        $request->validate([
            'date' => 'nullable|date', // يمكن إرسال التاريخ أو أخذ اليوم الحالي
        ]);

        // ✅ تعيين التاريخ الافتراضي لليوم الحالي إذا لم يتم تمريره
        $date = $request->input('date', Carbon::now()->toDateString());

        // ✅ البحث عن الموظف
        $employee = Employee::find($employeeId);
        if (! $employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        // ✅ جلب بيانات التحركات (المسار)
        $coordinates = EmployeeCoordinate::where('employee_id', $employeeId)
            ->whereDate('timestamp', $date)
            ->orderBy('timestamp', 'asc')
            ->get(['latitude', 'longitude', 'timestamp']);

        // ✅ جلب بيانات المنطقة (Zone) الخاصة بالموظف
        $zone = Zone::whereHas('employees', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })->first(['lat', 'longg', 'area']);
        // ✅ تحويل القيم إلى double قبل الإرجاع
        if ($zone) {
            $zone->lat = (float) $zone->lat;
            $zone->longg = (float) $zone->longg;
        }

        return response()->json([
            'status' => 'success',
            'employee' => [
                'id' => $employee->id,
                'name' => "{$employee->first_name} {$employee->family_name}",
            ],
            'route' => $coordinates,
            'zone' => $zone,
        ]);
    }

    public function getRecentEmployeeLocationsOld()
    {
        // وقت الحد الفاصل (10 دقائق)
        $thresholdTime = Carbon::now('Asia/Riyadh')->subMinutes(10);

        // الموظفون الذين تم تسجيل إحداثياتهم خلال آخر 10 دقائق
        $activeEmployees = Employee::whereHas('coordinates', function ($query) use ($thresholdTime) {
            $query->where('timestamp', '>=', $thresholdTime);
        })->with(['coordinates' => function ($query) {
            $query->latest('timestamp')->limit(1);
        }])->get(['id', 'first_name', 'father_name', 'grandfather_name', 'family_name', 'national_id', 'mobile_number', 'out_of_zone']);

        // الموظفون الذين تجاوز آخر تسجيل لهم أكثر من 10 دقائق
        $inactiveEmployees = Employee::whereDoesntHave('coordinates', function ($query) use ($thresholdTime) {
            $query->where('timestamp', '>=', $thresholdTime);
        })->get(['id', 'first_name', 'father_name', 'grandfather_name', 'family_name', 'national_id', 'mobile_number', 'out_of_zone']);

        // تحويل البيانات إلى JSON مع **full_name**
        $activeEmployeesData = $activeEmployees->map(function ($employee) {
            return [
                'id' => $employee->id,
                'full_name' => "{$employee->first_name} {$employee->father_name} {$employee->grandfather_name} {$employee->family_name}",
                'national_id' => $employee->national_id,
                'mobile_number' => $employee->mobile_number,
                'out_of_zone' => $employee->out_of_zone,
            ];
        });

        $inactiveEmployeesData = $inactiveEmployees->map(function ($employee) {
            return [
                'id' => $employee->id,
                'full_name' => "{$employee->first_name} {$employee->father_name} {$employee->grandfather_name} {$employee->family_name}",
                'national_id' => $employee->national_id,
                'mobile_number' => $employee->mobile_number,
                'out_of_zone' => $employee->out_of_zone,
            ];
        });

        return response()->json([
            'active_employees' => $activeEmployeesData,
            'inactive_employees' => $inactiveEmployeesData,
        ]);
    }

    public function getRecentEmployeeLocations()
    {
        $thresholdTime = Carbon::now('Asia/Riyadh')->subMinutes(10);

        // ✅ جلب آخر تسجيل لكل موظف نشط خلال آخر 10 دقائق
        $activeEmployees = DB::table('employees')
            ->join('employee_coordinates', 'employees.id', '=', 'employee_coordinates.employee_id')
            ->where('employee_coordinates.timestamp', '>=', $thresholdTime)
            ->select(
                'employees.id',
                DB::raw("CONCAT(employees.first_name, ' ', employees.father_name, ' ', employees.grandfather_name, ' ', employees.family_name) as full_name"),
                'employees.national_id',
                'employees.mobile_number',
                'employees.out_of_zone',
                DB::raw('MAX(employee_coordinates.latitude) as latitude'),
                DB::raw('MAX(employee_coordinates.longitude) as longitude'),
                DB::raw('MAX(employee_coordinates.timestamp) as last_seen')
            )
            ->groupBy('employees.id', 'employees.first_name', 'employees.father_name', 'employees.grandfather_name', 'employees.family_name', 'employees.national_id', 'employees.mobile_number', 'employees.out_of_zone')
            ->orderByDesc('last_seen')
            ->get();

        // ✅ جلب الموظفين الذين لم يسجلوا أي إحداثيات خلال آخر 10 دقائق
        $inactiveEmployees = DB::table('employees')
            ->leftJoin('employee_coordinates', function ($join) use ($thresholdTime) {
                $join->on('employees.id', '=', 'employee_coordinates.employee_id')
                    ->where('employee_coordinates.timestamp', '>=', $thresholdTime);
            })
            ->whereNull('employee_coordinates.id') // لم يسجلوا موقعًا حديثًا
            ->select(
                'employees.id',
                DB::raw("CONCAT(employees.first_name, ' ', employees.father_name, ' ', employees.grandfather_name, ' ', employees.family_name) as full_name"),
                'employees.national_id',
                'employees.mobile_number',
                'employees.out_of_zone'
            )
            ->groupBy('employees.id', 'employees.first_name', 'employees.father_name', 'employees.grandfather_name', 'employees.family_name', 'employees.national_id', 'employees.mobile_number', 'employees.out_of_zone')
            ->get();

        return response()->json([
            'active_employees' => $activeEmployees,
            'inactive_employees' => $inactiveEmployees,
        ]);
    }

    public function getActiveAndInactiveEmployees()
{
    $currentTime = Carbon::now('Asia/Riyadh');
    $today = $currentTime->toDateString();

    // جلب جميع الورديات النشطة حاليًا
    $currentShifts = Shift::whereHas('zone.pattern', function ($query) use ($currentTime) {
        $query->whereRaw('(DATEDIFF(?, start_date) % (working_days + off_days)) < working_days', [$currentTime]);
    })->get();

    // جلب الموظفين المتوقع عملهم في الورديات الحالية
    $expectedEmployees = EmployeeProjectRecord::whereIn('shift_id', $currentShifts->pluck('id'))
        ->where(function ($query) use ($today) {
            $query->whereNull('end_date')->orWhere('end_date', '>=', $today);
        })
        ->with('employee')
        ->get()
        ->map(function ($record) {
            return [
                'id' => $record->employee->id,
                'full_name' => "{$record->employee->first_name} {$record->employee->father_name} {$record->employee->grandfather_name} {$record->employee->family_name}",
                'national_id' => $record->employee->national_id,
                'mobile_number' => $record->employee->mobile_number,
                'out_of_zone' => $record->employee->out_of_zone,
                'shift_id' => $record->shift_id,
                'zone_id' => $record->zone_id,
            ];
        });

    // جلب بيانات الحضور للموظفين المتوقعين
    $attendances = Attendance::whereIn('employee_id', $expectedEmployees->pluck('id'))
        ->whereDate('date', $today)
        ->get()
        ->keyBy('employee_id');

    // جلب آخر إحداثيات لكل موظف خلال آخر 10 دقائق
    $lastCoordinates = EmployeeCoordinate::whereIn('employee_id', $expectedEmployees->pluck('id'))
        ->where('timestamp', '>=', Carbon::now('Asia/Riyadh')->subMinutes(10))
        ->orderBy('timestamp', 'desc')
        ->get()
        ->keyBy('employee_id');

    $activeEmployees = [];
    $inactiveEmployees = [];

    foreach ($expectedEmployees as $employee) {
        $attendance = $attendances->get($employee['id']);
        $coordinate = $lastCoordinates->get($employee['id']);

        if ($attendance && $attendance->status == 'present' && $coordinate) {
            $activeEmployees[] = array_merge($employee, [
                'latitude' => $coordinate->latitude,
                'longitude' => $coordinate->longitude,
                'last_seen' => $coordinate->timestamp,
            ]);
        } else {
            $inactiveEmployees[] = $employee;
        }
    }

    return response()->json([
        'active_employees' => $activeEmployees,
        'inactive_employees' => $inactiveEmployees,
    ]);
}

}
