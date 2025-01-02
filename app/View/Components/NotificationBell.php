<?php
namespace App\View\Components;

use Illuminate\View\Component;

class NotificationBell extends Component
{
    public $notifications;
    public $notificationCount;

    public function __construct()
    {
        $this->notifications = auth()->user()->unreadNotifications ?? [];
        $this->notificationCount = count($this->notifications);
    }

    public function render()
    {
        return view('components.notification-bell');
    }
}
