<?php
namespace App\Notifications;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use App\Channels\WhatsappChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RequestStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $request;
    public $status;
    public $approver;
    public $comments;

    public function __construct($request, $status, $approver, $comments = null)
    {
        $this->request = $request;
        $this->status = $status;
        $this->approver = $approver;
        $this->comments = $comments;
    }

    public function via($notifiable)
    {
        if ($notifiable instanceof Employee) {
            return ['database', WhatsappChannel::class];
        } elseif ($notifiable instanceof User) {
            return ['mail'];
        }
    
        return ['database'];
    }
    

    public function toArray($notifiable)
    {
        return [
            'request_id' => $this->request->id,
            'type' => $this->request->type,
            'status' => $this->status,
            'approver' => $this->approver->name ?? 'N/A',
            'comments' => $this->comments,
        ];
    }

    public function toWhatsapp($notifiable)
    {
        $statusMessage = $this->status === 'approved' 
            ? "تمت الموافقة على طلبك" 
            : "تم رفض طلبك";
    
        $commentsMessage = $this->comments 
            ? "السبب: {$this->comments}" 
            : "لا توجد تعليقات إضافية.";
    
        return "{$statusMessage}:\n" .
               "رقم الطلب: {$this->request->id}\n" .
               "نوع الطلب: {$this->request->type}\n" .
               "{$commentsMessage}\n" .
               "شكراً لاستخدام نظامنا.";
    }
    
}
