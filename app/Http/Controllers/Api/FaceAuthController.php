<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RekognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

        return response()->json([
            'success' => true,
            'face_id' => $record['FaceId'] ?? null,
            'image_id' => $record['ImageId'] ?? null,
            'employee_id' => $data['employee_id'],
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

    // ✅ نفس أسلوب enroll
    $collectionId = config('services.rekognition.collection') ?: env('AWS_REKOGNITION_COLLECTION');
    if (empty($collectionId)) {
        return response()->json([
            'success' => false,
            'message' => 'Collection ID مفقود. رجاءً عرّف AWS_REKOGNITION_COLLECTION في .env ثم أعد تشغيل الكاش.',
        ], 500);
    }

    $this->rek->ensureCollection($collectionId);

    $bytes = $this->getImageBytes($request);
    $threshold = (float)($data['threshold'] ?? 95.0);

    $client = $this->rek->client();

    try {
        $search = $client->searchFacesByImage([
            'CollectionId' => $collectionId,
            'Image' => ['Bytes' => $bytes],
            'MaxFaces' => 1,
            'FaceMatchThreshold' => max(0, min(100, $threshold)),
            'QualityFilter' => 'AUTO',
        ]);
    } catch (\Aws\Rekognition\Exception\RekognitionException $e) {
        // رسائل مفيدة عند الخطأ (صلاحيّات/منطقة/التنسيق)
        return response()->json([
            'success' => false,
            'passed'  => false,
            'message' => 'AWS Rekognition error: '.$e->getAwsErrorMessage(),
        ], 500);
    }

    $match = $search['FaceMatches'][0] ?? null;

    if (!$match) {
        return response()->json([
            'success' => false,
            'passed'  => false,
            'message' => 'لا يوجد تطابق كافٍ.',
        ], 401);
    }

    $similarity = (float)($match['Similarity'] ?? 0);
    $matchedExternal = $match['Face']['ExternalImageId'] ?? null;
    $matchedEmployeeId = is_numeric($matchedExternal) ? (int)$matchedExternal : null;
    $sameEmployee = $matchedEmployeeId === (int) $data['employee_id'];

    $passed = $sameEmployee && $similarity >= $threshold;

    return response()->json([
        'success'             => $passed,
        'passed'              => $passed,
        'similarity'          => $similarity,
        'required_threshold'  => $threshold,
        'matched_employee_id' => $matchedEmployeeId,
        'message'             => $passed ? 'تم التحقق بنجاح.' : 'فشل التحقق.',
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
