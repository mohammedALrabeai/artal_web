<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\NewEmployeeNotification;

class NotificationController extends Controller
{
    // الحصول على كل الإشعارات للمستخدم الحالي
    public function index()
    {
        $user = auth()->user();
        return response()->json([
            'unread' => $user->unreadNotifications,
            'read' => $user->readNotifications,
        ]);
    }

    // تحديد إشعار كمقروء
    public function markAsRead($id)
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['message' => 'تم تحديد الإشعار كمقروء']);
        }

        return response()->json(['message' => 'الإشعار غير موجود'], 404);
    }

    // تحديد كل الإشعارات كمقروءة
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'تم تحديد جميع الإشعارات كمقروءة']);
    }

    // حذف إشعار
    public function destroy($id)
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->delete();
            return response()->json(['message' => 'تم حذف الإشعار']);
        }

        return response()->json(['message' => 'الإشعار غير موجود'], 404);
    }

    // إرسال إشعار تجريبي
    public function sendTestNotification()
    {
        $user = User::first();
        $employee = \App\Models\Employee::first();
        
        if ($user && $employee) {
            $user->notify(new NewEmployeeNotification($employee));
            return response()->json(['message' => 'تم إرسال إشعار تجريبي']);
        }

        return response()->json(['message' => 'فشل إرسال الإشعار'], 400);
    }
} 