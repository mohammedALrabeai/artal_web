<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OtpService
{
    protected $baseUrl = 'https://otp.intshar.net/send_msg.php';
    protected $authorization = '40703bb7812b727ec01c24f2da518c407342559c';
    // protected $profileId = 'aedd0dc2-8453';
    protected $profileId = '35ab7ec0-63dd';


    public function sendOtp(string $phone, string $message): bool
    {
        $response = Http::asForm()->post($this->baseUrl, [
            'Authorization' => $this->authorization,
            'recipient' => $phone,
            'profile_id' => $this->profileId,
            'message' => $message,
        ]);

        return $response->ok();
    }
}
