<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCoordinate;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
    $zone->lat = (double) $zone->lat;
    $zone->longg = (double) $zone->longg;
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




    public function getRecentEmployeeLocations()
    {
        // وقت الحد الفاصل (10 دقائق)
        $thresholdTime = Carbon::now('Asia/Riyadh')->subMinutes(10);

        // الموظفون الذين تم تسجيل إحداثياتهم خلال آخر 10 دقائق
        $activeEmployees = Employee::whereHas('coordinates', function ($query) use ($thresholdTime) {
            $query->where('timestamp', '>=', $thresholdTime);
        })->with(['coordinates' => function ($query) {
            $query->latest('timestamp')->limit(1);
        }])->get();

        // الموظفون الذين تجاوز آخر تسجيل لهم أكثر من 10 دقائق
        $inactiveEmployees = Employee::whereDoesntHave('coordinates', function ($query) use ($thresholdTime) {
            $query->where('timestamp', '>=', $thresholdTime);
        })->get();

        return response()->json([
            'active_employees' => $activeEmployees,
            'inactive_employees' => $inactiveEmployees,
        ]);
    }
}
