<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class AreasUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $areas;

    public function __construct($areas)
    {
        $this->areas = $areas;
    }

    public function broadcastOn()
    {
        return new Channel('areas');  // تغيير اسم القناة ليتطابق مع الجافاسكربت
    }

    public function broadcastAs()
    {
        return 'areas-updated';  // تغيير اسم الحدث ليتطابق مع الجافاسكربت
    }
}
