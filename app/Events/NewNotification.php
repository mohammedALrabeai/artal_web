<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class NewNotification implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $notificationData;

    public function __construct($notificationData)
    {
        $this->notificationData = $notificationData;
    }

    public function broadcastOn()
    {
        return new Channel('notifications'); // القناة التي سيتم البث عليها
    }

    public function broadcastAs()
    {
        return 'new-notification'; // اسم الحدث الذي سيتم استقباله في Flutter
    }
}
