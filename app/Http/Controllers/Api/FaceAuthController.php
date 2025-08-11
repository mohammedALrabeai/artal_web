<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RekognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FaceAuthController extends Controller
{
    public function __construct(private RekognitionService $rek)
    {
        //
    }

    /**
     * Enrollment: فهرسة وجه الموظف داخل Collection
     * POST /api/face/enroll
     * form-data: employee_id (int), image (file) أو image_base64 (string)
     */
    public function enroll(Request $request)
    {
        $data = $request->validate([
            'employee_id'   => 'required|integer',
            'image'         => 'nullable|image|max:5120', // 5MB
            'image_base64'  => 'nullable|string',
        ]);

        if (!$request->hasFile('image') && empty($data['image_base64'])) {
            throw ValidationException::withMessages([
                'image' => 'أرفق صورة عبر image أو image_base64.',
            ]);
        }

        $collectionId = config('services.rekognition.collection') ?: env('AWS_REKOGNITION_COLLECTION');

        if (empty($collectionId)) {
            return response()->json([
                'success' => false,
                'message' => 'Collection ID مفقود. رجاءً عرّف AWS_REKOGNITION_COLLECTION في ملف .env ثم أعد تشغيل الكاش.',
            ], 500);
        }
        $this->rek->ensureCollection($collectionId);

        // اجلب bytes للصورة
        $bytes = $this->getImageBytes($request);

        $client = $this->rek->client();

        // فهرسة الوجه
        $res = $client->indexFaces([
            'CollectionId' => $collectionId,
            'ExternalImageId' => (string) $data['employee_id'],
            'Image' => ['Bytes' => $bytes],
            'QualityFilter' => 'AUTO',  // يحاول تجاهل الصور الرديئة
            'MaxFaces' => 1,
            // 'DetectionAttributes' => ['DEFAULT'], // اتركها افتراضي
        ]);

        $record = $res['FaceRecords'][0]['Face'] ?? null;

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم اكتشاف وجه مناسب. رجاءً التقط صورة أوضح.',
            ], 422);
        }

        // ✅ حفظ صورة التسجيل محليًا (قرص public) وإرجاع المسار
        $disk = 'public'; // تخزين داخل الاستضافة
        $dir  = "employees/{$data['employee_id']}/face/enrollment";
        $ts   = now('Asia/Riyadh')->format('Ymd_His');
        $imagePath = null;
        $imageUrl  = null;

        try {
            // 1) Multipart
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $ext  = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $name = "{$ts}_enroll.{$ext}";
                Storage::disk($disk)->putFileAs($dir, $file, $name);
                $imagePath = "{$dir}/{$name}";
                $imageUrl  = Storage::disk($disk)->url($imagePath);
            }
            // 2) Base64
            elseif (!empty($data['image_base64'])) {
                $raw = $data['image_base64'];
                $ext = 'jpg';
                if (str_contains($raw, ',')) {
                    [$meta, $raw] = explode(',', $raw, 2);
                    if (preg_match('#^data:image/(jpeg|jpg|png|webp)#i', $meta, $m)) {
                        $map = ['jpeg' => 'jpg', 'jpg' => 'jpg', 'png' => 'png', 'webp' => 'webp'];
                        $ext = $map[strtolower($m[1])] ?? 'jpg';
                    }
                }
                $name = "{$ts}_enroll.{$ext}";
                Storage::disk($disk)->put("{$dir}/{$name}", base64_decode($raw, true) ?: '');
                $imagePath = "{$dir}/{$name}";
                $imageUrl  = Storage::disk($disk)->url($imagePath);
            }
        } catch (\Throwable $e) {
            // لا نُفشل التسجيل لو فشل التخزين؛ فقط نسجّل الخطأ
            Log::warning('Enroll image save failed', [
                'employee_id' => $data['employee_id'],
                'error' => $e->getMessage(),
            ]);
        }

        // ⚠️ إن رغبت: تحديث حالة الموظف إلى enrolled (لو لديك الدالة جاهزة)
        $employee = \App\Models\Employee::find($data['employee_id']);
        $employee?->markFaceEnrolled();

        return response()->json([
            'success'     => true,
            'face_id'     => $record['FaceId'] ?? null,
            'image_id'    => $record['ImageId'] ?? null,
            'employee_id' => $data['employee_id'],
            'image_path'  => $imagePath, // مسار داخل التخزين المحلي
            'image_url'   => $imageUrl,  // رابط عام عبر /storage
        ]);
    }


    /**
     * Verification: التحقق قبل التحضير
     * POST /api/face/verify
     * form-data: employee_id (int), image (file) أو image_base64 (string)
     */
    public function verify(Request $request)
    {
        $data = $request->validate([
            'employee_id'   => 'required|integer',
            'image'         => 'nullable|image|max:5120',
            'image_base64'  => 'nullable|string',
            'threshold'     => 'nullable|numeric|min:50|max:100',
        ]);

        if (!$request->hasFile('image') && empty($data['image_base64'])) {
            throw ValidationException::withMessages([
                'image' => 'أرفق صورة عبر image أو image_base64.',
            ]);
        }

        $collectionId = config('services.rekognition.collection') ?: env('AWS_REKOGNITION_COLLECTION');
        if (empty($collectionId)) {
            return response()->json([
                'success' => false,
                'passed'  => false,
                'message' => 'Collection ID مفقود. رجاءً عرّف AWS_REKOGNITION_COLLECTION في .env ثم أعد تشغيل الكاش.',
            ], 500);
        }

        $this->rek->ensureCollection($collectionId);

        $bytes = $this->getImageBytes($request);
        $threshold = (float)($data['threshold'] ?? 95.0);

        $client = $this->rek->client();

        try {
            $search = $client->searchFacesByImage([
                'CollectionId'       => $collectionId,
                'Image'              => ['Bytes' => $bytes],
                'MaxFaces'           => 1,
                'FaceMatchThreshold' => max(0, min(100, $threshold)),
                'QualityFilter'      => 'AUTO',
            ]);
        } catch (\Aws\Rekognition\Exception\RekognitionException $e) {
            return response()->json([
                'success' => false,
                'passed'  => false,
                'message' => 'AWS Rekognition error: ' . $e->getAwsErrorMessage(),
            ], 500);
        }

        $match = $search['FaceMatches'][0] ?? null;

        // إعدادات التخزين: مجلد موحّد لكل موظف + اسم ملف يتضمن التاريخ والوقت
        $disk       = 'public';
        $tz         = 'Asia/Riyadh';
        $employeeId = (int)$data['employee_id'];
        $dir        = "employees/{$employeeId}/face/verify";
        $stamp      = now($tz)->format('Y-m-d_H-i-s'); // تاريخ + وقت

        // دالة حفظ مع ضمان اسم فريد
        $saveImage = function () use ($request, $data, $disk, $dir, $stamp) {
            try {
                Storage::disk($disk)->makeDirectory($dir);

                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $ext  = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                    $base = "{$stamp}_verify";
                    $final = "{$base}.{$ext}";
                    $i = 1;
                    while (Storage::disk($disk)->exists("{$dir}/{$final}")) {
                        $final = "{$base}-{$i}.{$ext}";
                        $i++;
                    }
                    Storage::disk($disk)->putFileAs($dir, $file, $final);
                    $path = "{$dir}/{$final}";
                    return [$path, Storage::disk($disk)->url($path)];
                }

                // Base64
                $raw = $data['image_base64'];
                $ext = 'jpg';
                if (str_contains($raw, ',')) {
                    [$meta, $raw] = explode(',', $raw, 2);
                    if (preg_match('#^data:image/(jpeg|jpg|png|webp)#i', $meta, $m)) {
                        $map = ['jpeg' => 'jpg', 'jpg' => 'jpg', 'png' => 'png', 'webp' => 'webp'];
                        $ext = $map[strtolower($m[1])] ?? 'jpg';
                    }
                }
                $binary = base64_decode($raw, true);
                if ($binary === false) {
                    throw new \RuntimeException('Base64 decode failed.');
                }

                $base  = "{$stamp}_verify";
                $final = "{$base}.{$ext}";
                $i = 1;
                while (Storage::disk($disk)->exists("{$dir}/{$final}")) {
                    $final = "{$base}-{$i}.{$ext}";
                    $i++;
                }
                Storage::disk($disk)->put("{$dir}/{$final}", $binary);
                $path = "{$dir}/{$final}";
                return [$path, Storage::disk($disk)->url($path)];
            } catch (\Throwable $e) {
                Log::warning('Verify image save failed', [
                    'employee_id' => $data['employee_id'] ?? null,
                    'error'       => $e->getMessage(),
                ]);
                return [null, null];
            }
        };

        if (!$match) {
            [$imagePath, $imageUrl] = $saveImage();
            return response()->json([
                'success'    => false,
                'passed'     => false,
                'message'    => 'لا يوجد تطابق كافٍ.',
                'image_path' => $imagePath,
                'image_url'  => $imageUrl,
            ], 401);
        }

        $similarity       = (float)($match['Similarity'] ?? 0);
        $matchedExternal  = $match['Face']['ExternalImageId'] ?? null;
        $matchedEmployeeId = is_numeric($matchedExternal) ? (int)$matchedExternal : null;
        $sameEmployee     = $matchedEmployeeId === $employeeId;

        $passed = $sameEmployee && $similarity >= $threshold;

        // احفظ الصورة دائمًا سواء نجحت المطابقة أم لا
        [$imagePath, $imageUrl] = $saveImage();

        return response()->json([
            'success'             => $passed,
            'passed'              => $passed,
            'similarity'          => $similarity,
            'required_threshold'  => $threshold,
            'matched_employee_id' => $matchedEmployeeId,
            'message'             => $passed ? 'تم التحقق بنجاح.' : 'فشل التحقق.',
            'image_path'          => $imagePath,
            'image_url'           => $imageUrl,
        ], $passed ? 200 : 401);
    }




    /**
     * جلب bytes للصورة من ملف مُحمّل أو base64
     */
    private function getImageBytes(Request $request): string
    {
        if ($file = $request->file('image')) {
            return file_get_contents($file->getRealPath());
        }

        $b64 = $request->string('image_base64');
        if ($b64 && Str::contains($b64, ',')) {
            // دعم data URI مثل: data:image/jpeg;base64,...
            $b64 = Str::after($b64, ',');
        }
        return base64_decode($b64);
    }
}
