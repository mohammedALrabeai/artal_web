<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
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
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New Coverage Request',
            'message' => 'Employee ' . $this->attendance->employee->first_name . ' submitted a coverage request.',
            'attendance_id' => $this->attendance->id,
        ];
    }
}
