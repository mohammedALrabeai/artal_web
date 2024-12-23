<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class CustomNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $body;
    protected $url;

    public function __construct($title, $body, $url = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->url = $url;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ]);
    }
}
