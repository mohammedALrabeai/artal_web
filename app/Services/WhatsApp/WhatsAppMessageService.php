<?php 

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppMessageService
{
    protected string $baseUrl = 'https://wappi.pro/api/sync';
    protected string $profileId = '0589178f-06d5';
    protected string $authToken = '40703bb7812b727ec01c24f2da518c407342559c';

    public function sendMessage(string $phoneNumber, string $message): bool
    {
        $url = "{$this->baseUrl}/message/send?profile_id={$this->profileId}";

        $response = Http::withHeaders([
            'Authorization' => $this->authToken,
        ])->post($url, [
            'recipient' => $phoneNumber,
            'body' => $message,
        ]);

        if (!$response->successful()) {
            Log::error("Failed to send message to {$phoneNumber}", [
                'response' => $response->json()
            ]);
            return false;
        }

        return true;
    }
}
