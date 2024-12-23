<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Employee;
use App\Notifications\NewEmployeeNotification;

class AdminNotificationController extends Controller
{
    // إرسال إشعار تجريبي لجميع المدراء
    public function sendTestNotificationToAllManagers()
    {
        $managers = User::whereIn('role', ['manager', 'general_manager', 'hr'])->get();
        $employee = Employee::first();
        
        if (!$employee) {
            return response()->json(['message' => 'لا يوجد موظفين في النظام'], 404);
        }

        foreach ($managers as $manager) {
            $manager->notify(new NewEmployeeNotification($employee));
        }

        return response()->json([
            'message' => 'تم إرسال إشعار تجريبي لجميع المدراء',
            'managers_count' => $managers->count()
        ]);
    }

    // إرسال إشعار لمدير محدد
    public function sendTestNotificationToManager($managerId)
    {
        $manager = User::find($managerId);
        if (!$manager) {
            return response()->json(['message' => 'المدير غير موجود'], 404);
        }

        $employee = Employee::first();
        if (!$employee) {
            return response()->json(['message' => 'لا يوجد موظفين في النظام'], 404);
        }

        $manager->notify(new NewEmployeeNotification($employee));

        return response()->json([
            'message' => 'تم إرسال إشعار تجريبي للمدير',
            'manager_name' => $manager->name
        ]);
    }

    // الحصول على قائمة المدراء مع عدد إشعاراتهم
    public function getManagersWithNotifications()
    {
        $managers = User::whereIn('role', ['manager', 'general_manager', 'hr'])
            ->get()
            ->map(function ($manager) {
                return [
                    'id' => $manager->id,
                    'name' => $manager->name,
                    'role' => $manager->role,
                    'unread_notifications' => $manager->unreadNotifications->count(),
                    'total_notifications' => $manager->notifications->count()
                ];
            });

        return response()->json([
            'managers' => $managers
        ]);
    }

    // حذف جميع الإشعارات
    public function deleteAllNotifications()
    {
        $managers = User::whereIn('role', ['manager', 'general_manager', 'hr'])->get();
        
        foreach ($managers as $manager) {
            $manager->notifications()->delete();
        }

        return response()->json([
            'message' => 'تم حذف جميع الإشعارات بنجاح'
        ]);
    }
} 