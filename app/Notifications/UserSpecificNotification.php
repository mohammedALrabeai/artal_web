<?php

namespace App\Notifications;


use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserSpecificNotification extends Notification
{
    use Queueable;

    public $message;
    public $type;
    public $role;

    /**
     * إنشاء إشعار جديد.
     */
    public function __construct($message, $type, $role = null)
    {
        $this->message = $message;
        $this->type = $type;
        $this->role = $role;
    }

    /**
     * تحديد القنوات المستخدمة للإشعار.
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * حفظ البيانات في قاعدة البيانات.
     */
    public function toDatabase($notifiable)
    {
        return [
            'message' => $this->message,
            'type' => $this->type,
            'role' => $this->role,
        ];
    }
}
