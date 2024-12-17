<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;

class AreaCreatedNotification extends Notification
{
    use Queueable;

    protected $area;

    public function __construct($area)
    {
        $this->area = $area;
    }

    public function via($notifiable)
    {
        return ['database']; // تحديد إرسال الإشعار إلى قاعدة البيانات
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New Area Added',
            'message' => 'A new area has been created: ' . $this->area->name,
            'area_id' => $this->area->id,
        ];
    }
}
