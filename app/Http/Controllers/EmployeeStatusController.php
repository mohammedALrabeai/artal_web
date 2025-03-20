<?php

namespace App\Http\Controllers;

use App\Models\EmployeeStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeStatusController extends Controller
{
    public function index(Request $request)
    {
        // نحدد العتبة الزمنية بـ15 دقيقة من الوقت الحالي
        $threshold = now()->subMinutes(15);

        $employeeStatuses = EmployeeStatus::with('employee')
            ->orderByRaw('CASE WHEN gps_enabled = 0 OR last_seen_at < ? THEN 1 ELSE 0 END DESC', [$threshold])
            ->orderBy('last_seen_at', 'desc')
            ->paginate(20);

        return response()->json($employeeStatuses);
    }

    /**
     * تحديث حالة الموظف بناءً على بيانات heartbeat.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request)
    {
        // الحصول على المستخدم المُصادق عليه
        $employee = Auth::user();
        if (! $employee) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $employeeId = $employee->id;

        // استلام البيانات من الطلب
        $gpsEnabled = $request->boolean('gps_enabled', false);
        $isInside = $request->boolean('is_inside', false);
        $lastLocation = $request->input('last_location'); // من المتوقع أن تكون بيانات الموقع JSON أو مصفوفة

        // إيجاد السجل الخاص بالموظف أو إنشاؤه إذا لم يكن موجوداً
        $status = EmployeeStatus::firstOrNew(['employee_id' => $employeeId]);

        // تحديث وقت آخر اتصال
        $status->last_seen_at = Carbon::now();

        // تحديث حالة GPS: إذا تغيرت القيمة يتم تحديث وقت تغيير حالة GPS
        if ($status->gps_enabled !== $gpsEnabled) {
            $status->gps_enabled = $gpsEnabled;
            $status->last_gps_status_at = Carbon::now();
        }

        // تحديث حالة النطاق (is_inside) وإذا تغيرت القيمة يتم تحديث وقت تغيير حالة النطاق
        if ($status->is_inside !== $isInside) {
            $status->is_inside = $isInside;
            // يمكن إضافة عمود آخر مثل zone_status_updated_at إذا أردت تتبع التغيير
            // $status->zone_status_updated_at = Carbon::now();
        }

        // تحديث الموقع إذا تم إرساله
        if ($lastLocation) {
            $status->last_location = is_array($lastLocation)
                ? json_encode($lastLocation)
                : $lastLocation;
        }

        $status->save();

        return response()->json(['message' => 'Employee status updated successfully']);
    }
}
