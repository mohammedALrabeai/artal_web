<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class OperationNotificationController extends Controller
{
    /**
     * ✅ جلب إشعارات المستخدم مع دعم التصفية والتقسيم إلى صفحات
     */
    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user(); // التحقق من المستخدم عبر API Token

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // جلب آخر 20 إشعارًا لكل صفحة وتصفيتها حسب `type` الخاص بلوحة التحكم في Flutter
        $notifications = $user->notifications()
            ->where('type', 'App\Notifications\CoverageRequestNotification') // جلب الإشعارات الخاصة بلوحة تحكم Flutter فقط
            ->orderBy('created_at', 'desc')
            ->paginate(20); // تقسيم النتائج إلى صفحات

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications,
        ]);
    }

    /**
     * ✅ وضع علامة "مقروء" على إشعار معين
     */
    public function markAsRead($id)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $notification = $user->notifications()->find($id);

        if ($notification) {
            $notification->update(['read_at' => now()]); // تحديث حالة القراءة
            return response()->json(['message' => 'Notification marked as read']);
        }

        return response()->json(['message' => 'Notification not found'], 404);
    }
}
