<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RekognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\EmployeeFaceEvent;
use App\Models\Employee;




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
        $employee?->markFaceEnrolled($imagePath);

        try {
            if ($imagePath) {
                EmployeeFaceEvent::create([
                    'employee_id' => (int) $data['employee_id'],
                    'type'        => EmployeeFaceEvent::TYPE_ENROLL,
                    'disk'        => $disk,          // 'public'
                    'path'        => $imagePath,     // مثل employees/{id}/face/enrollment/...
                    'captured_at' => now('Asia/Riyadh'),
                    'rek_face_id' => $record['FaceId']  ?? null,
                    'rek_image_id' => $record['ImageId'] ?? null,
                    'meta'        => [
                        'source' => 'mobile',
                        'ip'     => $request->ip(),
                        // أضف ما تحتاجه
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Enroll event db save failed', [
                'employee_id' => $data['employee_id'],
                'error'       => $e->getMessage(),
            ]);
        }


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

            // NEW (DB event): حفظ سجل التحقق حتى لو فشل
            try {
                if ($imagePath) {
                    EmployeeFaceEvent::create([
                        'employee_id' => (int) $data['employee_id'],
                        'type'        => EmployeeFaceEvent::TYPE_VERIFY,
                        'disk'        => $disk,
                        'path'        => $imagePath,
                        'captured_at' => now($tz),
                        'similarity'  => null,
                        'rek_face_id' => null,
                        'rek_image_id' => null,
                        'meta'        => [
                            'passed'    => false,
                            'threshold' => (float)($data['threshold'] ?? 95.0),
                            'ip'        => $request->ip(),
                        ],
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Verify event db save failed (no match)', [
                    'employee_id' => $data['employee_id'],
                    'error'       => $e->getMessage(),
                ]);
            }

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

        // احفظ الصورة دائمًا سواء نجحت المطابقة أم لا


        // NEW (DB event): حفظ سجل التحقق
        try {
            if ($imagePath) {
                EmployeeFaceEvent::create([
                    'employee_id' => $employeeId,
                    'type'        => EmployeeFaceEvent::TYPE_VERIFY,
                    'disk'        => $disk,
                    'path'        => $imagePath,
                    'captured_at' => now($tz),
                    'similarity'  => $similarity, // نسبة المطابقة
                    'rek_face_id' => $match['Face']['FaceId']  ?? null,
                    'rek_image_id' => $match['Face']['ImageId'] ?? null,
                    'meta'        => [
                        'passed'              => (bool) $passed,
                        'threshold'           => $threshold,
                        'matched_employee_id' => $matchedEmployeeId,
                        'ip'                  => $request->ip(),
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Verify event db save failed', [
                'employee_id' => $employeeId,
                'error'       => $e->getMessage(),
            ]);
        }


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



    /**
 * GET /api/face/employees/{employee}/today
 * يرجع صورة التسجيل الأساسية + جميع تحقق اليوم مع التفاصيل
 */
public function todayVerifications(int $employee, Request $request)
{
    $tz = 'Asia/Riyadh';

    /** @var Employee|null $emp */
    $emp = Employee::find($employee);
    if (!$emp) {
        return response()->json([
            'success' => false,
            'message' => 'الموظف غير موجود.',
        ], 404);
    }

    // صورة التسجيل الأساسية (من جدول الأحداث إن وجدت، وإلا من حقل employee.face_image)
    $baseEnroll = EmployeeFaceEvent::latestEnrollFor($emp->id)->first();
    $baseImageUrlFromEmployee = $emp->face_image
        ? Storage::disk('public')->url($emp->face_image)
        : null;

    // تحقق اليوم
    $todayVerifies = EmployeeFaceEvent::forEmployee($emp->id)
        ->verifies()
        ->today($tz)
        ->latestFirst()
        ->get();

    // تحويل النتائج إلى مصفوفة مرتبة
    $verifications = $todayVerifies->map(function (EmployeeFaceEvent $ev) {
        return [
            'id'         => $ev->id,
            'captured_at'=> optional($ev->captured_at)->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
            'similarity' => $ev->similarity !== null ? (float) $ev->similarity : null,
            'passed'     => (bool) data_get($ev->meta, 'passed', false),
            'threshold'  => data_get($ev->meta, 'threshold'),
            'url'        => $ev->url,        // من الـ accessor
            'thumb_url'  => $ev->thumb_url,  // من الـ accessor (أو نفس الأصل إن لا يوجد مصغّر)
            'rek_face_id'=> $ev->rek_face_id,
            'rek_image_id'=> $ev->rek_image_id,
            'meta'       => $ev->meta,
        ];
    })->values();

    return response()->json([
        'success'      => true,
        'employee_id'  => $emp->id,
        'date'         => now($tz)->toDateString(),
        'base' => [
            'enrollment_event' => $baseEnroll ? [
                'id'          => $baseEnroll->id,
                'captured_at' => optional($baseEnroll->captured_at)->timezone($tz)->format('Y-m-d H:i:s'),
                'url'         => $baseEnroll->url,
                'thumb_url'   => $baseEnroll->thumb_url,
                'path'        => $baseEnroll->path,
                'disk'        => $baseEnroll->disk,
                'rek_face_id' => $baseEnroll->rek_face_id,
                'rek_image_id'=> $baseEnroll->rek_image_id,
            ] : null,
            'employee_face_image_url' => $baseImageUrlFromEmployee, // fallback (حقل employee.face_image)
        ],
        'today' => [
            'total'         => $verifications->count(),
            'verifications' => $verifications,
        ],
    ]);
}

/**
 * GET /api/face/today?employee_id=123
 * نفس استجابة todayVerifications ولكن عبر كويري بارامتر
 */
public function todayVerificationsQuery(Request $request)
{
    $data = $request->validate([
        'employee_id' => 'required|integer',
    ]);

    return $this->todayVerifications((int) $data['employee_id'], $request);
}


}
