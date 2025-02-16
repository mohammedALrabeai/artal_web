<?php

namespace App\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class CoverageRequestNotification extends Notification
{
    use Queueable;

    protected $attendance;

    public function __construct($attendance)
    {
        $this->attendance = $attendance;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast']; // تخزين الإشعار في قاعدة البيانات + بثه
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'طلب تغطية جديد',
            'message' => $this->buildMessage(),
            'attendance_id' => $this->attendance->id,
            'employee_id' => $this->attendance->employee->id,
            'employee_name' => $this->attendance->employee->first_name.' '.
                $this->attendance->employee->father_name.' '.
                $this->attendance->employee->family_name,
            'date' => $this->attendance->date,
            'check_in' => $this->attendance->check_in ?? 'غير متوفر',
            'check_out' => $this->attendance->check_out ?? 'غير متوفر',
            'zone' => $this->attendance->zone->name ?? 'غير محدد',
            'reason' => $this->attendance->notes ?? 'لا يوجد سبب محدد',
            'status' => $this->attendance->approval_status ?? 'في انتظار الموافقة',
            'is_coverage' => $this->attendance->is_coverage ? 'نعم' : 'لا',
            'out_of_zone' => $this->attendance->out_of_zone ? 'نعم' : 'لا',
        ];
    }

    // public function toBroadcast($notifiable)
    // {
    //     return new BroadcastMessage([
    //         'title' => 'طلب تغطية جديد',
    //         'message' => $this->buildMessage(),
    //         'attendance_id' => $this->attendance->id,
    //         'employee_id' => $this->attendance->employee->id,
    //         'employee_name' => $this->attendance->employee->first_name.' '.
    //             $this->attendance->employee->father_name.' '.
    //             $this->attendance->employee->family_name,
    //         'date' => $this->attendance->date,
    //         'check_in' => $this->attendance->check_in ?? 'غير متوفر',
    //         'check_out' => $this->attendance->check_out ?? 'غير متوفر',
    //         'zone' => $this->attendance->zone->name ?? 'غير محدد',
    //         'reason' => $this->attendance->notes ?? 'لا يوجد سبب محدد',
    //         'status' => $this->attendance->approval_status ?? 'في انتظار الموافقة',
    //         'is_coverage' => $this->attendance->is_coverage ? 'نعم' : 'لا',
    //         'out_of_zone' => $this->attendance->out_of_zone ? 'نعم' : 'لا',
    //     ]);
    // }
    public function toBroadcast($notifiable)
{
    return new BroadcastMessage([
        'id' => (string) \Str::uuid(), // UUID لضمان التوافق مع Flutter
        'type' => 'App\\Notifications\\CoverageRequestNotification',
        'notifiable_id' => $notifiable->id,
        'notifiable_type' => get_class($notifiable),
        'title' => 'طلب تغطية جديد',
        'message' => $this->buildMessage(),
        'employee_id' => $this->attendance->employee->id,
        'employee_name' => $this->attendance->employee->first_name . ' ' .
            $this->attendance->employee->father_name . ' ' .
            $this->attendance->employee->family_name,
        'date' => $this->attendance->date,
        'check_in' => $this->attendance->check_in ?? 'غير متوفر',
        'check_out' => $this->attendance->check_out ?? 'غير متوفر',
        'zone' => $this->attendance->zone->name ?? 'غير محدد',
        'reason' => $this->attendance->notes ?? 'لا يوجد سبب محدد',
        'status' => $this->attendance->approval_status ?? 'في انتظار الموافقة',
        'is_coverage' => $this->attendance->is_coverage ? 'نعم' : 'لا',
        'out_of_zone' => $this->attendance->out_of_zone ? 'نعم' : 'لا',
        'attendance_id' => $this->attendance->id,
        'created_at' => now()->toDateTimeString(),
        'read_at' => null, // عند إرسال الإشعار يكون غير مقروء
    ]);
}


    private function buildMessage()
    {
        return "📢 **طلب تغطية جديد**\n"
            ."👤 **الموظف:** {$this->attendance->employee->first_name} "
            ."{$this->attendance->employee->father_name} "
            ."{$this->attendance->employee->family_name} "
            ."(ID: {$this->attendance->employee->id})\n"
            ."📅 **التاريخ:** {$this->attendance->date}\n"
            .'⏰ **الحضور:** '.($this->attendance->check_in ?? 'غير متوفر')."\n"
            .'🏁 **الانصراف:** '.($this->attendance->check_out ?? 'غير متوفر')."\n"
            .'📍 **الموقع:** '.($this->attendance->zone->name ?? 'غير محدد')."\n"
            .'📝 **السبب:** '.($this->attendance->notes ?? 'لا يوجد سبب محدد')."\n"
            .'🔄 **الحالة:** '.($this->attendance->approval_status ?? 'في انتظار الموافقة')."\n"
            .'🔄 **هل هي تغطية؟** '.($this->attendance->is_coverage ? 'نعم' : 'لا')."\n"
            .'🚨 **خارج المنطقة؟** '.($this->attendance->out_of_zone ? 'نعم' : 'لا');
    }

    public function broadcastOn()
    {
        return new Channel('notifications'); // تأكد أن القناة صحيحة
    }

    public function broadcastAs()
    {
        return 'new-notification'; // تأكد أن الحدث هو نفسه الذي تستخدمه في Flutter
    }
}
