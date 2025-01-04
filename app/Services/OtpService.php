<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OtpService
{
    protected $baseUrl = 'https://login.isender360.com/api/sync/message/send';
    // protected $profileId = 'aedd0dc2-8453';
    protected $profileId = '35ab7ec0-63dd';


    public function sendOtp(string $phone, string $message): bool
    {
        $response = Http::asForm()->post($this->baseUrl.'?profile_id='.$this->profileId, [
       
            'recipient' => $phone,
           
            'body' => $message,
        ]);

        return $response->ok();
    }
    public function sendViaWhatsapp(string $phone, string $message): bool
    {
        if (empty($phone)) {
            \Log::error('Phone number is missing or invalid.', ['phone' => $phone]);
            return false;
        }
    
        \Log::info('Sending WhatsApp message...', ['phone' => $phone, 'message' => $message]);
    
        $response = Http::asForm()->post($this->baseUrl . '?profile_id=' . $this->profileId, [
            'recipient' => $phone,
            'body' => $message,
        ]);
    
        \Log::info('WhatsApp API Response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
    
        return $response->ok();
    }
    
    
}
