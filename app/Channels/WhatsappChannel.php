<?php

namespace App\Channels;
namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Services\OtpService;
use Illuminate\Support\Facades\Log;


class WhatsappChannel
{
    public function send($notifiable, Notification $notification)
    {
        try {
            $type = get_class($notifiable);
            Log::info('Notifiable Type:', ['type' => $type]);
    
            if ($type === 'App\Models\Employee') {
                Log::info('Notifiable is an Employee:', $notifiable->toArray());
            } elseif ($type === 'App\Models\User') {
                Log::info('Notifiable is a User:', $notifiable->toArray());
            } else {
                Log::error('Unknown Notifiable Type:', ['type' => $type]);
                return;
            }
    
            if (!method_exists($notification, 'toWhatsapp')) {
                Log::error('Notification missing toWhatsapp method.');
                return;
            }
    
            $message = $notification->toWhatsapp($notifiable);
    
            if (empty($notifiable->mobile_number)) {
                Log::error('Mobile number is missing for the notifiable entity.', [
                    'notifiable_id' => $notifiable->id,
                ]);
                return;
            }
    
            $otpService = new OtpService();
            $otpService->sendViaWhatsapp($notifiable->mobile_number, $message);
        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp message.', [
                'exception' => $e,
            ]);
        }
    }
    
    
    
    
    
    
    
    
    
}
