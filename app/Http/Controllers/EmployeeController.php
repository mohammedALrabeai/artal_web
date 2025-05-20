<?php

namespace App\Http\Controllers;

use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\EmployeeProjectRecord;
use App\Models\Project;
use App\Models\Shift;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $search = trim($request->input('search', ''));
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $query = Employee::with(['currentProjectRecord.project', 'latestZone'])
            ->select([
                'id',
                'first_name', 'father_name', 'grandfather_name', 'family_name',
                'national_id', 'mobile_number', 'phone_number', 'avatar_path',
            ])
            ->orderBy('first_name');

        if ($search !== '') {
            // نقسم نص البحث إلى كلمات
            $keywords = preg_split('/\s+/', $search);

            // لكل كلمة، نضيف شرط where فرعي يبحث عنها في أي من حقول الاسم
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->where(function ($sub) use ($word) {
                        $sub->where('first_name', 'like', "%{$word}%")
                            ->orWhere('father_name', 'like', "%{$word}%")
                            ->orWhere('grandfather_name', 'like', "%{$word}%")
                            ->orWhere('family_name', 'like', "%{$word}%");
                    });
                }
            });
        }

        // الكاش فقط إذا لم يكن هناك بحث
        $cacheKey = "employees:{$page}:{$perPage}:".md5($search);
        if ($search === '') {
            $paginator = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($query, $perPage) {
                return $query->paginate($perPage);
            });
        } else {
            $paginator = $query->paginate($perPage);
        }

        return EmployeeResource::collection($paginator)
            ->additional([
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
    }

    public function allowed(Request $request)
    {
        // 1. جلب الموظف المصادق عليه عبر جارد 'employee'
        $employee = $request->user('employee');
        // في حال أردت استخدام الـ facade:
        // $employee = \Auth::guard('employee')->user();

        // 2. استخلاص الـ ID
        $employeeId = $employee ? $employee->id : null;

        // 3. جلب القائمة من ملف الإعدادات
        $allowedIds = config('employee_interface.allowed_employee_ids', []);

        // 4. تحقق من وجود المعرف
        $allowed = $employeeId && in_array($employeeId, $allowedIds);

        // 5. إعادة JSON
        return response()->json([
            'allowed' => (bool) $allowed,
        ], 200);
    }

    public function store(Request $request)
    {
        // 1. تحقق من صحة المدخلات
        $data = $request->validate([
            'type' => 'required|in:transfer,exclude,new_employee',
            'employee_id' => 'required_if:type,transfer,exclude|integer|exists:employees,id',
            'zone_name' => 'required_if:type,transfer|string|max:255',
            'national_id' => 'required_if:type,new_employee|string|max:50',
            'full_name' => 'required_if:type,new_employee|string|max:255',
            'new_zone' => 'required_if:type,new_employee|string|max:255',
        ]);

        // 2. بناء عنوان ورسالة الإشعار
        switch ($data['type']) {
            case 'transfer':
                $employee = Employee::find($data['employee_id']);
                $title = 'طلب نقل موظف';
                $message = "👷‍♂️ الموظف: {$employee->name} (ID: {$employee->id})\n"
                         ."📌 نُقل إلى الموقع: {$data['zone_name']}";
                break;

            case 'exclude':
                $employee = Employee::find($data['employee_id']);
                $title = 'طلب استبعاد موظف';
                $message = "👷‍♂️ الموظف: {$employee->name} (ID: {$employee->id})\n"
                         .'⚠️ طلب استبعاده قُدم للمراجعة';
                break;

            case 'new_employee':
                $title = 'إضافة موظف جديد';
                $message = "🆕 موظف جديد\n"
                         ."🆔 الهوية: {$data['national_id']}\n"
                         ."👤 الاسم: {$data['full_name']}\n"
                         ."📍 الموقع: {$data['new_zone']}";
                break;
        }

        // // 3. إرسال الإشعار داخل النظام (لواجهة HR)
        // $notificationService = app(NotificationService::class);
        // $notificationService->sendNotification(
        //     ['hr'],        // فقط دور الموارد البشرية
        //     $title,
        //     $message,
        //     [
        //         $notificationService->createAction(
        //             'عرض الطلبات',
        //             '/admin/employee-actions',
        //             'heroicon-s-list'
        //         ),
        //     ]
        // );

        // 4. إرسال رسالة واتساب بنفس المحتوى عبر OtpService
        $otpService = new OtpService;

        // أ. إلى الموظف (في حال النقل أو الاستبعاد)
        // if (in_array($data['type'], ['transfer', 'exclude'])) {
        //     $otpService->sendOtp($employee->mobile_number, $message);
        // }

        // ب. إلى جروب الموارد البشرية
        $otpService->sendOtp(
            '120363385699307538@g.us',
            $message
        );

        // 5. رد الـ API
        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال الإشعار داخل النظام وعبر واتساب.',
        ], 200);
    }

    public function schedule(Request $request)
    {

        $employee = Employee::where('api_token', $request->bearerToken())->first();

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found.',
            ], 404);
        }
        // $employee = $request->user(); // الموظف المصادق عليه

        // استرجاع جدول الورديات الخاص بالموظف
        $shifts = Shift::where('zone_id', $employee->zone_id)
            ->whereBetween('start_date', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->get();

        return response()->json([
            'status' => 'success',
            'data' => $shifts,
        ]);
    }

    public function getEmployeeProjects(Request $request)
    {
        $employeeId = $request->user()->id; // الحصول على الموظف الحالي من التوكن

        $projects = Project::whereHas('employees', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })
            ->with([
                'zones' => function ($query) {
                    $query->with(['pattern', 'shifts']);
                },
            ])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $projects->map(function ($project) {
                return [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'description' => $project->description,
                    'area' => [
                        'id' => $project->area->id ?? null,
                        'name' => $project->area->name ?? null,
                    ],
                    'start_date' => $project->start_date,
                    'end_date' => $project->end_date,
                    'zones' => $project->zones->map(function ($zone) {
                        return [
                            'zone_id' => $zone->id,
                            'zone_name' => $zone->name,
                            'start_date' => $zone->start_date,
                            'pattern' => $zone->pattern ? [
                                'pattern_id' => $zone->pattern->id,
                                'name' => $zone->pattern->name,
                                'working_days' => $zone->pattern->working_days,
                                'off_days' => $zone->pattern->off_days,
                                'hours_cat' => $zone->pattern->hours_cat,
                            ] : null,
                            'lat' => $zone->lat,
                            'longg' => $zone->longg,
                            'area' => $zone->area,
                            'emp_no' => $zone->emp_no,

                            'shifts' => $zone->shifts->map(function ($shift) {
                                return [
                                    'shift_id' => $shift->id,
                                    'name' => $shift->name,
                                    'type' => $shift->type,
                                    'morning_start' => $shift->morning_start,
                                    'morning_end' => $shift->morning_end,
                                    'evening_start' => $shift->evening_start,
                                    'evening_end' => $shift->evening_end,
                                    'early_entry_time' => $shift->early_entry_time,
                                    'last_entry_time' => $shift->last_entry_time,
                                    'early_exit_time' => $shift->early_exit_time,
                                    'last_time_out' => $shift->last_time_out,
                                    'start_date' => $shift->start_date,
                                    'status' => $shift->status,
                                ];
                            }),
                        ];
                    }),
                ];
            }),
        ]);
    }

    public function getEmployeeZones(Request $request)
    {
        // استخراج الموظف باستخدام التوكن
        $employee = $request->user();

        if (! $employee) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $employeeZones = EmployeeProjectRecord::with(['project', 'zone', 'shift'])
            ->where('employee_id', $employee->id)
            ->get();

        if ($employeeZones->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No zones found for this employee.',
            ], 404);
        }

        $data = $employeeZones->map(function ($record) {
            return [
                'project' => [
                    'id' => $record->project->id ?? null,
                    'name' => $record->project->name ?? null,
                    'description' => $record->project->description ?? null,
                    'start_date' => $record->project->start_date ?? null,
                    'end_date' => $record->project->end_date ?? null,
                ],
                'zone' => [
                    'id' => $record->zone->id ?? null,
                    'name' => $record->zone->name ?? null,
                    'latitude' => $record->zone->lat ?? null,
                    'longitude' => $record->zone->longg ?? null,
                    'area_radius' => $record->zone->area ?? null,
                    'start_date' => $record->zone->start_date ?? null,
                ],
                'shift' => [
                    'id' => $record->shift->id ?? null,
                    'name' => $record->shift->name ?? null,
                    'type' => $record->shift->type ?? null,
                    'morning_start' => $record->shift->morning_start ?? null,
                    'morning_end' => $record->shift->morning_end ?? null,
                    'evening_start' => $record->shift->evening_start ?? null,
                    'evening_end' => $record->shift->evening_end ?? null,
                ],
                'status' => $record->status,
                'start_date' => $record->start_date,
                'end_date' => $record->end_date,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
