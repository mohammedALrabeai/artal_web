<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewEmployeeNotification extends Notification
{
    public function __construct(public $employee)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        // تجميع الاسم الكامل للموظف
        $fullName = implode(' ', array_filter([
            $this->employee->first_name,
            $this->employee->father_name,
            $this->employee->grandfather_name,
            $this->employee->family_name
        ]));

        return [
            'title' => 'موظف جديد',
            'message' => "تم إضافة موظف جديد: {$fullName}",
            'employee_id' => $this->employee->id,
            'data' => [
                'employee_id' => $this->employee->id,
                'first_name' => $this->employee->first_name,
                'family_name' => $this->employee->family_name,
                'full_name' => $fullName
            ]
        ];
    }
} 