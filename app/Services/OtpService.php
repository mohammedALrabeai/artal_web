<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OtpService
{
    protected $baseUrl = 'https://wappi.pro/api/sync/message/send';

    // protected $profileId = 'aedd0dc2-8453';
    // protected $profileId = '35ab7ec0-63dd';
    protected $profileId = '2fdc9526-cccd'; // 4c4327c7-ed84
    // protected $profileId = '4c4327c7-ed84';

    protected $apiToken = '40703bb7812b727ec01c24f2da518c407342559c';

    public function sendOtp(string $phone, string $message): bool
    {
        $response = Http::withHeaders([
            'accept' => 'application/json',
            'Authorization' => '40703bb7812b727ec01c24f2da518c407342559c',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl.'?profile_id='.$this->profileId, [
            'body' => $message,
            'recipient' => $phone,
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

        $response = Http::asForm()->post($this->baseUrl.'?profile_id='.$this->profileId, [
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
        ])->post('https://wappi.pro/api/sync/message/img/send?profile_id='.$this->profileId, $payload);

        // تسجيل الاستجابة
        \Log::info('WhatsApp API Response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return $response->ok();
    }

    public function sendViaWhatsappWithAttachment(
        string $phone,
        string $type,
        string $title,
        string $message,
        ?string $attachmentBase64 = null,
        ?string $fileName = null
    ): bool {
        // التحقق من صحة رقم الهاتف
        if (empty($phone)) {
            \Log::error('رقم الهاتف مفقود أو غير صالح.', ['phone' => $phone]);

            return false;
        }
        if ($type == 'notification') {
            $type = 'إخطار';
        }
        $type_tr = __($type);
        $caption = "{$type_tr}\n{$title}\n{$message}";

        // تحضير الحمولة (Payload) الأساسيّة
        $payload = [
            'recipient' => $phone,
            'caption' => $caption,
        ];

        // إذا كان هناك مرفق مع الإشعار، نقوم بتحديد نوعه حسب امتداد اسم الملف
        if (! empty($attachmentBase64) && ! empty($fileName)) {
            // استخراج امتداد الملف والحول إلى حروف صغيرة
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // إذا كان الامتداد من ضمن الصور نستخدم نقطة النهاية الخاصة بالصور
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $payload['b64_file'] = $attachmentBase64;
                // تعيين نقطة النهاية الخاصة بالصور
                $endpoint = 'https://wappi.pro/api/sync/message/img/send?profile_id='.$this->profileId;
            } else {
                // خلاف ذلك نفترض أنه ملف (وثيقة)
                $payload['b64_file'] = $attachmentBase64;
                $payload['file_name'] = $fileName;
                // تعيين نقطة النهاية الخاصة بالوثائق
                $endpoint = 'https://wappi.pro/api/sync/message/document/send?profile_id='.$this->profileId;
            }
        } else {
            // إذا لم يُقدم مرفق، يمكن إرسال رسالة نصية دون مرفق أو التعامل مع الحالة حسب الاحتياج
            // في هذا المثال نقوم بإرسال رسالة نصية عبر نقطة النهاية المخصصة (إذا توفرت)
            $payload['body'] = $caption;
            $endpoint = 'https://wappi.pro/api/sync/message/send?profile_id='.$this->profileId;
        }

        // \Log::info('إرسال رسالة واتساب مع مرفق...', [
        //     'phone' => $phone,
        //     'endpoint' => $endpoint,
        //     'payload' => $payload,
        // ]);

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => '40703bb7812b727ec01c24f2da518c407342559c', // تأكد من ضبط التوكن المناسب في البيئة
            'Content-Type' => 'application/json',
        ])->post($endpoint, $payload);

        \Log::info('استجابة واتساب API', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return $response->ok();
    }
}
