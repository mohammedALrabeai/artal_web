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
use Illuminate\Support\Carbon;


use Illuminate\Support\Facades\DB;








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

        // ✅ اليوم المطلوب (إن لم يرسل يعود إلى اليوم)
        $requestedDate = $request->query('date'); // شكل متوقع: YYYY-MM-DD
        $day          = $requestedDate
            ? Carbon::parse($requestedDate, $tz)->startOfDay()
            : now($tz)->startOfDay();

        $startOfDay = $day->copy();
        $endOfDay   = $day->copy()->endOfDay();

        // صورة التسجيل الأساسية (من جدول الأحداث إن وجدت، وإلا من حقل employee.face_image)
        $baseEnroll = EmployeeFaceEvent::latestEnrollFor($emp->id)->first();
        $baseImageUrlFromEmployee = $emp->face_image
            ? Storage::disk('public')->url($emp->face_image)
            : null;

        // ✅ تحقّقات اليوم المطلوب
        $verifiesQuery = EmployeeFaceEvent::query()
            ->where('employee_id', $emp->id)
            ->where('type', EmployeeFaceEvent::TYPE_VERIFY)
            ->whereBetween('captured_at', [$startOfDay, $endOfDay])
            ->orderByDesc('captured_at'); // بدلاً من latestFirst() لضمان الترتيب

        $todayVerifies = $verifiesQuery->get();

        // تحويل النتائج إلى مصفوفة مرتبة
        $verifications = $todayVerifies->map(function (EmployeeFaceEvent $ev) use ($tz) {
            return [
                'id'          => $ev->id,
                'captured_at' => optional($ev->captured_at)->timezone($tz)->format('Y-m-d H:i:s'),
                'similarity'  => $ev->similarity !== null ? (float) $ev->similarity : null,
                'passed'      => (bool) data_get($ev->meta, 'passed', false),
                'threshold'   => data_get($ev->meta, 'threshold'),
                'url'         => $ev->url,
                'thumb_url'   => $ev->thumb_url,
                'rek_face_id' => $ev->rek_face_id,
                'rek_image_id' => $ev->rek_image_id,
                'meta'        => $ev->meta,
            ];
        })->values();

        return response()->json([
            'success'     => true,
            'employee_id' => $emp->id,
            // ✅ التاريخ المعروض من الخادم هو اليوم المطلوب
            'date'        => $startOfDay->toDateString(),

            'base' => [
                'enrollment_event' => $baseEnroll ? [
                    'id'           => $baseEnroll->id,
                    'captured_at'  => optional($baseEnroll->captured_at)->timezone($tz)->format('Y-m-d H:i:s'),
                    'url'          => $baseEnroll->url,
                    'thumb_url'    => $baseEnroll->thumb_url,
                    'path'         => $baseEnroll->path,
                    'disk'         => $baseEnroll->disk,
                    'rek_face_id'  => $baseEnroll->rek_face_id,
                    'rek_image_id' => $baseEnroll->rek_image_id,
                ] : null,
                'employee_face_image_url' => $baseImageUrlFromEmployee,
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
            'date'        => 'nullable|date', // ✅ تاريخ اختياري
        ]);

        return $this->todayVerifications((int) $data['employee_id'], $request);
    }








    public function resetEnrollment(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|integer',
        ]);

        $employee = Employee::find($data['employee_id']);
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'الموظف غير موجود.',
            ], 404);
        }

        $tz           = 'Asia/Riyadh';
        $collectionId = config('services.rekognition.collection') ?: env('AWS_REKOGNITION_COLLECTION');

        // 1) حذف وجوه الموظف من Rekognition
        if (!empty($collectionId)) {
            try {
                $client  = $this->rek->client();

                // نجلب FaceIds المُسجلة سابقًا في أحداث enroll (أخفّ تكلفة)
                $faceIds = EmployeeFaceEvent::query()
                    ->where('employee_id', $employee->id)
                    ->where('type', EmployeeFaceEvent::TYPE_ENROLL)
                    ->whereNotNull('rek_face_id')
                    ->pluck('rek_face_id')
                    ->filter()
                    ->values()
                    ->all();

                // خطة بديلة: مسح المجموعة بحثًا عن ExternalImageId = employee_id (لو لم نكن نحفظ rek_face_id)
                if (empty($faceIds)) {
                    $token = null;
                    do {
                        $args = ['CollectionId' => $collectionId, 'MaxResults' => 4096];
                        if ($token) $args['NextToken'] = $token;
                        $out = $client->listFaces($args);
                        foreach ($out['Faces'] as $f) {
                            if (($f['ExternalImageId'] ?? null) === (string)$employee->id) {
                                $faceIds[] = $f['FaceId'];
                            }
                        }
                        $token = $out['NextToken'] ?? null;
                    } while ($token);
                }

                if (!empty($faceIds)) {
                    $client->deleteFaces([
                        'CollectionId' => $collectionId,
                        'FaceIds'      => array_values(array_unique($faceIds)),
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('resetEnrollment: purge Rekognition failed', [
                    'employee_id' => $employee->id,
                    'error'       => $e->getMessage(),
                ]);
                // لا نفشل الطلب
            }
        }

        // 2) حذف صور التسجيل فقط من التخزين (لا نلمس verify)
        try {
            $disk = 'public';
            $dir  = "employees/{$employee->id}/face/enrollment";
            if (Storage::disk($disk)->exists($dir)) {
                // حذف الملفات داخل مجلد enrollment فقط
                foreach (Storage::disk($disk)->allFiles($dir) as $file) {
                    Storage::disk($disk)->delete($file);
                }
                // حذف المجلدات الفارغة تحت enrollment
                foreach (Storage::disk($disk)->allDirectories($dir) as $sub) {
                    Storage::disk($disk)->deleteDirectory($sub);
                }
                // لا نحذف مجلد verify ولا أي شيء خارجه
            }
        } catch (\Throwable $e) {
            \Log::warning('resetEnrollment: purge enrollment storage failed', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
        }

        // (اختياري) تنظيف سجلات أحداث التسجيل فقط (نترك verify كما هي)
        try {
            EmployeeFaceEvent::query()
                ->where('employee_id', $employee->id)
                ->where('type', EmployeeFaceEvent::TYPE_ENROLL)
                ->delete(); // لو عندك SoftDeletes استعمل ->forceDelete() حسب سياستك
        } catch (\Throwable $e) {
            \Log::warning('resetEnrollment: delete enroll events failed', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
        }

        // 3) تصفير حالة الوجه ورابط الصورة
        $employee->markFaceNotEnrolled(true); // true = تصفير face_image

        return response()->json([
            'success'     => true,
            'message'     => 'تم حذف وجوه Rekognition وصور التسجيل فقط. الموظف جاهز لتسجيل صورة جديدة.',
            'employee_id' => $employee->id,
            'status'      => $employee->face_enrollment_status, // not_enrolled
            'face_image'  => $employee->face_image, // null الآن
            'info'        => [
                'rekognition_collection' => $collectionId,
                'deleted_only'           => 'enrollment images',
                'verify_images_untouched' => true,
            ],
        ]);
    }

    public function daySummary(Request $request)
    {
        $tz = 'Asia/Riyadh';
        // التاريخ اختياري؛ افتراضي اليوم بتوقيت الرياض
        $dateStr = $request->query('date', now($tz)->toDateString());

        try {
            $dayStartLocal = Carbon::parse($dateStr, $tz)->startOfDay();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'صيغة التاريخ غير صحيحة. استخدم YYYY-MM-DD.',
            ], 422);
        }

        // حول إلى UTC (يفترض أن الحقل captured_at محفوظ على UTC)
        $fromUtc = $dayStartLocal->clone()->setTimezone('UTC');
        $toUtc   = $dayStartLocal->clone()->endOfDay()->setTimezone('UTC');

        // نستهدف سجلات التحقق فقط
        $baseQuery = EmployeeFaceEvent::query()
            ->where('type', EmployeeFaceEvent::TYPE_VERIFY)
            ->whereBetween('captured_at', [$fromUtc, $toUtc]);

        // الإجماليات
        $total  = (clone $baseQuery)->count();
        // JSON_EXTRACT(meta, '$.passed') يرجع true/false في MySQL 5.7+ / 8
        $passed = (clone $baseQuery)
            ->whereRaw("JSON_EXTRACT(meta, '$.passed') = true")
            ->count();
        $failed = $total - $passed;

        // تجميع لكل موظف
        $perEmployee = (clone $baseQuery)
            ->selectRaw("
            employee_id,
            COUNT(*) AS verifies_count,
            SUM(CASE WHEN JSON_EXTRACT(meta, '$.passed') = true THEN 1 ELSE 0 END) AS passed_count,
            SUM(CASE WHEN JSON_EXTRACT(meta, '$.passed') = true THEN 0 ELSE 1 END) AS failed_count,
            MAX(captured_at) AS last_time
        ")
            ->groupBy('employee_id');

        // اربط للحصول على تفاصيل آخر محاولة (similarity, passed) + اسم الموظف
        $items = DB::table(DB::raw("({$perEmployee->toSql()}) x"))
            ->mergeBindings($perEmployee->getQuery())
            ->leftJoin('employee_face_events as e2', function ($j) {
                $j->on('e2.employee_id', '=', 'x.employee_id')
                    ->on('e2.captured_at', '=', 'x.last_time');
            })
            ->leftJoin('employees as emp', 'emp.id', '=', 'x.employee_id')
            ->selectRaw("
            x.employee_id,
            CONCAT(COALESCE(emp.first_name, ''), ' ', COALESCE(emp.family_name, '')) as employee_name,
            x.verifies_count,
            x.passed_count,
            x.failed_count,
            x.last_time,
            e2.similarity as last_similarity,
            JSON_EXTRACT(e2.meta, '$.passed') as last_passed
        ")
            ->orderByDesc('x.verifies_count')
            ->get()
            ->map(function ($row) use ($tz) {
                return [
                    'employee_id'    => (int)$row->employee_id,
                    'employee_name'  => trim($row->employee_name) ?: (string)$row->employee_id,
                    'verifies_count' => (int)$row->verifies_count,
                    'passed_count'   => (int)$row->passed_count,
                    'failed_count'   => (int)$row->failed_count,
                    'last_time'      => $row->last_time ? Carbon::parse($row->last_time, 'UTC')->setTimezone($tz)->format('Y-m-d H:i:s') : null,
                    'last_similarity' => $row->last_similarity !== null ? (float)$row->last_similarity : null,
                    'last_passed'    => $row->last_passed === 'true' || $row->last_passed === 1,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'date'    => $dayStartLocal->toDateString(),
            'stats'   => [
                'total'     => $total,
                'passed'    => $passed,
                'failed'    => $failed,
                'pass_rate' => $total ? round($passed / max(1, $total) * 100, 2) : 0.0,
            ],
            'items'   => $items,
        ]);
    }

    public function dayDetail(Request $request)
    {
        $tz = 'Asia/Riyadh';
        $data = $request->validate([
            'employee_id' => 'required|integer',
            'date'        => 'nullable|date_format:Y-m-d',
        ]);

        $emp = Employee::find($data['employee_id']);
        if (!$emp) {
            return response()->json([
                'success' => false,
                'message' => 'الموظف غير موجود.',
            ], 404);
        }

        $dateStr = $data['date'] ?? now($tz)->toDateString();
        try {
            $dayStartLocal = Carbon::parse($dateStr, $tz)->startOfDay();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'صيغة التاريخ غير صحيحة. استخدم YYYY-MM-DD.',
            ], 422);
        }
        $fromUtc = $dayStartLocal->clone()->setTimezone('UTC');
        $toUtc   = $dayStartLocal->clone()->endOfDay()->setTimezone('UTC');

        // صورة التسجيل الأساسية: أحدث enroll أو fallback من عمود employee.face_image
        $enrollEvent = EmployeeFaceEvent::query()
            ->where('employee_id', $emp->id)
            ->where('type', EmployeeFaceEvent::TYPE_ENROLL)
            ->latest('captured_at')
            ->first();

        $enrollUrl = null;
        if ($enrollEvent?->path && $enrollEvent?->disk) {
            try {
                $enrollUrl = Storage::disk($enrollEvent->disk)->url($enrollEvent->path);
            } catch (\Throwable $e) {
                $enrollUrl = null;
            }
        }
        $fallbackUrl = $emp->face_image ? Storage::disk('public')->url($emp->face_image) : null;

        // عمليات تحقق اليوم
        $verifies = EmployeeFaceEvent::query()
            ->where('employee_id', $emp->id)
            ->where('type', EmployeeFaceEvent::TYPE_VERIFY)
            ->whereBetween('captured_at', [$fromUtc, $toUtc])
            ->orderByDesc('captured_at')
            ->get()
            ->map(function (EmployeeFaceEvent $ev) use ($tz) {
                return [
                    'id'          => $ev->id,
                    'captured_at' => optional($ev->captured_at)->timezone($tz)->format('Y-m-d H:i:s'),
                    'similarity'  => $ev->similarity !== null ? (float)$ev->similarity : null,
                    'passed'      => (bool)data_get($ev->meta, 'passed', false),
                    'threshold'   => data_get($ev->meta, 'threshold'),
                    'url'         => $ev->url,       // accessors في الموديل
                    'thumb_url'   => $ev->thumb_url, // أو نفس الأصل إن ما في مصغّر
                    'rek_face_id'  => $ev->rek_face_id,
                    'rek_image_id' => $ev->rek_image_id,
                    'meta'         => $ev->meta,
                ];
            })
            ->values();

        return response()->json([
            'success'     => true,
            'employee_id' => $emp->id,
            'date'        => $dayStartLocal->toDateString(),
            'base'        => [
                'enrollment_event' => $enrollEvent ? [
                    'id'          => $enrollEvent->id,
                    'captured_at' => optional($enrollEvent->captured_at)->timezone($tz)->format('Y-m-d H:i:s'),
                    'url'         => $enrollEvent->url,
                    'thumb_url'   => $enrollEvent->thumb_url,
                    'path'        => $enrollEvent->path,
                    'disk'        => $enrollEvent->disk,
                    'rek_face_id'  => $enrollEvent->rek_face_id,
                    'rek_image_id' => $enrollEvent->rek_image_id,
                ] : null,
                'employee_face_image_url' => $fallbackUrl,
            ],
            'today' => [
                'total'         => $verifies->count(),
                'verifications' => $verifies,
            ],
        ]);
    }

    public function dayGallery(Request $request)
    {
        $tz      = 'Asia/Riyadh';
        $dateStr = $request->query('date', now($tz)->toDateString());
        $limit   = (int) min(max((int)$request->query('limit', 100), 1), 200);
        $filter  = $request->query('filter', 'all'); // all|passed|failed
        $q       = trim((string)$request->query('q', ''));
        $cursor  = $request->query('cursor'); // صيغة: "2025-08-20T14:52:45Z|12345"

        try {
            $dayLocal = Carbon::parse($dateStr, $tz)->startOfDay();
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'صيغة التاريخ غير صحيحة (YYYY-MM-DD).'], 422);
        }

        // نطاق اليوم بتوقيت UTC
        $fromUtc = $dayLocal->clone()->setTimezone('UTC');
        $toUtc   = $dayLocal->clone()->endOfDay()->setTimezone('UTC');

        // قاعدة البيانات: نستهدف سجلات التحقق فقط
        $qbase = EmployeeFaceEvent::query()
            ->where('type', EmployeeFaceEvent::TYPE_VERIFY)
            ->whereBetween('captured_at', [$fromUtc, $toUtc]);

        // فلتر النجاح/الفشل
        if ($filter === 'passed') {
            $qbase->whereRaw("JSON_EXTRACT(meta, '$.passed') = true");
            // بدائل MariaDB:
            // $qbase->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.passed')) AS UNSIGNED) = 1");
        } elseif ($filter === 'failed') {
            $qbase->where(function ($w) {
                $w->whereNull('meta')
                    ->orWhereRaw("JSON_EXTRACT(meta, '$.passed') IS NULL")
                    ->orWhereRaw("JSON_EXTRACT(meta, '$.passed') = false");
                // بديل:
                // ->orWhereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.passed')) AS UNSIGNED) = 0");
            });
        }

        // بحث بالاسم/الرقم (انضمام على employees)
        if ($q !== '') {
            $qbase->join('employees as emp', 'emp.id', '=', 'employee_face_events.employee_id')
                ->where(function ($w) use ($q) {
                    $w->where('emp.id', $q)
                        ->orWhere('emp.first_name', 'like', "%{$q}%")
                        ->orWhere('emp.family_name', 'like', "%{$q}%")
                        ->orWhere(DB::raw("CONCAT(COALESCE(emp.first_name,''),' ',COALESCE(emp.family_name,''))"), 'like', "%{$q}%");
                })
                ->select('employee_face_events.*'); // أعد اختيار الأعمدة الأصلية
        }

        // ترتيب ثابت: الأحدث أولًا (captured_at DESC, id DESC)
        $qbase->orderByDesc('captured_at')->orderByDesc('id');

        // Cursor: (captured_at < T) OR (captured_at = T AND id < ID)
        if ($cursor) {
            // نتوقع "ISO|id"
            [$ctStr, $cidStr] = array_pad(explode('|', $cursor, 2), 2, null);
            try {
                $ct = Carbon::parse($ctStr); // cursor time UTC أو ISO
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'صيغة cursor غير صحيحة.'], 422);
            }
            $cid = (int) $cidStr;
            $qbase->where(function ($w) use ($ct, $cid) {
                $w->where('captured_at', '<', $ct)
                    ->orWhere(function ($w2) use ($ct, $cid) {
                        $w2->where('captured_at', '=', $ct)
                            ->where('id', '<', $cid);
                    });
            });
        }

        // نجلب limit+1 لاكتشاف وجود صفحة تالية
        $rows = $qbase->limit($limit + 1)->get();

        $hasMore = $rows->count() > $limit;
        if ($hasMore) $rows = $rows->slice(0, $limit);

        // لضم اسم الموظف بكفاءة، نجلب الأسماء دفعة واحدة
        $empIds = $rows->pluck('employee_id')->unique()->values();
        $names = Employee::whereIn('id', $empIds)->get(['id', 'first_name', 'family_name'])
            ->mapWithKeys(fn($e) => [$e->id => trim(($e->first_name ?? '') . ' ' . ($e->family_name ?? ''))])
            ->all();

        // بناء العناصر
        $items = $rows->map(function (EmployeeFaceEvent $ev) use ($tz, $names) {
            $url  = method_exists($ev, 'getUrlAttribute')     ? $ev->url     : ($ev->path && $ev->disk ? Storage::disk($ev->disk)->url($ev->path) : null);
            $turl = method_exists($ev, 'getThumbUrlAttribute') ? $ev->thumb_url : $url;

            return [
                'id'            => $ev->id,
                'employee_id'   => (int)$ev->employee_id,
                'employee_name' => $names[$ev->employee_id] ?? (string)$ev->employee_id,
                'captured_at'   => optional($ev->captured_at)->timezone($tz)->format('Y-m-d H:i:s'),
                'similarity'    => $ev->similarity !== null ? (float)$ev->similarity : null,
                'passed'        => (bool) data_get($ev->meta, 'passed', false),
                'threshold'     => data_get($ev->meta, 'threshold'),
                'url'           => $url,
                'thumb_url'     => $turl,
            ];
        })->values();

        // next cursor من آخر عنصر
        $nextCursor = null;
        if ($hasMore && $items->isNotEmpty()) {
            $last = $rows->last();
            // captured_at يجب أن يكون UTC في القاعدة
            $nextCursor = optional($last->captured_at)->copy()->setTimezone('UTC')->toIso8601String() . '|' . $last->id;
        }

        return response()->json([
            'success' => true,
            'date'    => $dayLocal->toDateString(),
            'page'    => [
                'limit'       => $limit,
                'has_more'    => $hasMore,
                'next_cursor' => $nextCursor,
            ],
            'items'   => $items,
        ]);
    }
}



// للاستعلام عن عدد الاستخدام لكل موظف 
// SELECT employee_id, COUNT(*) AS total_events FROM employee_face_events GROUP BY employee_id ORDER BY total_events DESC;
