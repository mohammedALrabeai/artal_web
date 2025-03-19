<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeeStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class EmployeeStatusController extends Controller
{
    /**
     * تحديث حالة الموظف بناءً على بيانات heartbeat.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request)
    {
        // التأكد من وجود employee_id في الطلب
        // $employeeId = $request->input('employee_id');$employee = Auth::user(); 
        $employee = Auth::user();
        if (!$employee) {
          // unauthorized 
            return response()->json(['error' => 'Unauthorized'], 401);
          
        }
        $employeeId = $employee->id;
        // if (!$employeeId) {
        //     return response()->json(['error' => 'Employee ID is required'], 422);
        // }

        // استلام البيانات من الطلب
        $gpsEnabled  = $request->boolean('gps_enabled', false);
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

        // تحديث الموقع إذا تم إرساله
        if ($lastLocation) {
            // يمكن أن تقوم بعملية التحقق أو التحويل حسب الحاجة
            $status->last_location = is_array($lastLocation)
                ? json_encode($lastLocation)
                : $lastLocation;
        }

        $status->save();

        return response()->json(['message' => 'Employee status updated successfully']);
    }
}
