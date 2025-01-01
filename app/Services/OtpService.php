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
}
