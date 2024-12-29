<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
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

    /**
     * Create a new notification instance.
     */
    public function __construct($request, $status, $approver, $comments = null)
    {
        $this->request = $request; // الطلب
        $this->status = $status; // حالة الطلب (موافقة، رفض)
        $this->approver = $approver; // الشخص الذي قام بالموافقة أو الرفض
        $this->comments = $comments; // ملاحظات إضافية (اختياري)
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database']; // يتم إرسال الإشعار بالبريد الإلكتروني وحفظه في قاعدة البيانات
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $statusMessage = $this->status === 'approved' 
            ? 'تمت الموافقة على طلبك' 
            : 'تم رفض طلبك';

        $approverName = $this->approver->name ?? 'المراجع';

        return (new MailMessage)
            ->subject('تحديث حالة الطلب')
            ->line($statusMessage . ' بواسطة: ' . $approverName)
            ->line('رقم الطلب: ' . $this->request->id)
            ->line('نوع الطلب: ' . $this->request->type)
            ->line($this->comments ? 'ملاحظات: ' . $this->comments : '')
            ->action('عرض الطلب', url('/requests/' . $this->request->id))
            ->line('شكراً لاستخدام نظامنا!');
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray($notifiable): array
    {
        return [
            'request_id' => $this->request->id,
            'request_type' => $this->request->type,
            'status' => $this->status,
            'approver' => $this->approver->name ?? 'المراجع',
            'comments' => $this->comments,
        ];
    }
}
