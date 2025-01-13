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


public function markAsRead($id)
    {
        $employee = Auth::user(); // جلب المستخدم بناءً على التوكن

        if (!$employee) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // العثور على الإشعار والتحقق من أنه يخص الموظف
        $notification = EmployeeNotification::where('id', $id)
            ->where('employee_id', $employee->id)
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found or unauthorized'], 404);
        }

        // تحديث حالة الإشعار إلى "تم قراءته"
        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read successfully']);
    }
 
}
