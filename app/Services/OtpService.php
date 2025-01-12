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



//     public function sendViaWhatsappWithImage(string $phone, string $message, ?string $imageBase64 = null, ?string $caption = null): bool
// {
//     if (empty($phone)) {
//         \Log::error('Phone number is missing or invalid.', ['phone' => $phone]);
//         return false;
//     }

//     \Log::info('Sending WhatsApp message...', [
//         'phone' => $phone,
//         'message' => $message,
//         'image' => $imageBase64 ? 'Included' : 'Not included',
//     ]);

//     // إعداد البيانات
//     $payload = [
//         'recipient' => $phone,
//         'caption' => $caption ?? $message,
//     ];

//     if ($imageBase64) {
//         $payload['b64_file'] = $imageBase64;
//     }

//     // إرسال الطلب
//     $response = Http::withHeaders([
//         'accept' => 'application/json',
//        'Authorization' => '40703bb7812b727ec01c24f2da518c407342559c',
//         'Content-Type' => 'application/json',
//     ])->post('https://wappi.pro/api/sync/message/img/send?profile_id=' . $this->profileId, $payload);

//     // تسجيل الاستجابة
//     \Log::info('WhatsApp API Response', [
//         'status' => $response->status(),
//         'body' => $response->body(),
//     ]);

//     return $response->ok();
// }

public function sendViaWhatsappWithImage(string $phone, string $type, string $title, string $message, ?string $imageBase64 = null): bool
{
    if (empty($phone)) {
        \Log::error('Phone number is missing or invalid.', ['phone' => $phone]);
        return false;
    }

    \Log::info('Sending WhatsApp message...', [
        'phone' => $phone,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'image' => $imageBase64 ? 'Included' : 'Not included',
    ]);

    // تكوين النص المرسل بعد الصورة
    $caption = " {$type}\n";
    $caption .= " {$title}\n";
    $caption .= " {$message}";

    // إعداد البيانات
    $payload = [
        'recipient' => $phone,
        'caption' => $caption,
    ];

    if ($imageBase64) {
        $payload['b64_file'] = $imageBase64;
    }

    // إرسال الطلب
    $response = Http::withHeaders([
        'accept' => 'application/json',
        'Authorization' => '40703bb7812b727ec01c24f2da518c407342559c',
        'Content-Type' => 'application/json',
    ])->post('https://wappi.pro/api/sync/message/img/send?profile_id=' . $this->profileId, $payload);

    // تسجيل الاستجابة
    \Log::info('WhatsApp API Response', [
        'status' => $response->status(),
        'body' => $response->body(),
    ]);

    return $response->ok();
}

    
}
