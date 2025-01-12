<?php
namespace App\Http\Controllers;

use App\Models\EmployeeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeNotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        $employee = Auth::user(); // جلب الموظف بناءً على التوكن

        if (!$employee) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // جلب الإشعارات الخاصة بالموظف
        $notifications = EmployeeNotification::where('employee_id', $employee->id)
            ->whereNull('deleted_at') // استثناء الإشعارات المحذوفة
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'notifications' => $notifications,
        ]);
    }


    public function getUnreadCount()
{
    $employee = Auth::user(); // جلب الموظف بناءً على التوكن

    if (!$employee) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // حساب عدد الإشعارات غير المقروءة
    $unreadCount = EmployeeNotification::where('employee_id', $employee->id)
        ->where('is_read', false)
        ->whereNull('deleted_at') // استثناء الإشعارات المحذوفة
        ->count();

    return response()->json([
        'unread_count' => $unreadCount,
    ]);
}

}
