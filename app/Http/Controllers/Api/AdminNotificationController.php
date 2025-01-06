<?php

namespace App\Http\Controllers\Api;

use App\Filament\Resources\EmployeeResource;
use App\Http\Controllers\Controller;
use Filament\Facades\Filament;
use App\Models\User;
use App\Models\Employee;
use App\Notifications\NewEmployeeNotification;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class AdminNotificationController extends Controller
{
    // إرسال إشعار تجريبي لجميع المدراء
    public function sendTestNotificationToAllManagers()
    {
        $managers = User::whereIn('role', ['manager', 'general_manager', 'hr'])->get();
        $employee = Employee::first();

        $fullName = implode(' ', array_filter([
            $employee->first_name,
            $employee->father_name,
            $employee->grandfather_name,
            $employee->family_name
        ]));
        
        if (!$employee) {
            return response()->json(['message' => 'لا يوجد موظفين في النظام'], 404);
        }

        foreach ($managers as $manager) {
            // $manager->notify(new NewEmployeeNotification($employee));

            Notification::make()
            ->title('موظف جديد')
            ->body( "تم إضافة موظف جديد: {$fullName}")
            ->info()
            ->viewData(['employee' => $employee])
            ->actions([
                Action::make('view')
                    ->button()
                    ->url(EmployeeResource::getUrl('view', ['record' => $employee->id]), shouldOpenInNewTab: false),
                Action::make('undo')
                    ->color('gray')->close(),
            ])
            ->persistent()
            ->sendToDatabase($manager, isEventDispatched: true)
            ->broadcast($manager);
           
        }

        return response()->json([
            'message' => 'تم إرسال إشعار تجريبي لجميع المدراء',
            'managers_count' => $managers->count(),
            'employee' => $employee,
            'managers' => $managers
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