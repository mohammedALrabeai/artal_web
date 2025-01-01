<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OtpService
{
    protected $baseUrl = 'https://login.isender360.com/api/sync/message/send';
    protected $profileId = '35ab7ec0-63dd';

    public function sendOtp(string $phone, string $message): bool
    {
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl, [
            'profile_id' => $this->profileId,
            'body' => $message,
            'recipient' => $phone,
        ]);
        return $response->ok();
    }
}
