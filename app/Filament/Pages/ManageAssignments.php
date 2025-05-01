<?php

namespace App\Filament\Pages;

use App\Forms\Components\EmployeeSelectV2;
use App\Models\EmployeeProjectRecord;
use App\Models\Project;
use App\Models\Shift;
use App\Models\Zone;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ManageAssignments extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    // protected static ?string $navigationLabel = 'إسناد الموظفين';

    // protected static ?string $navigationGroup = 'إدارة المشاريع';

    protected static string $view = 'filament.pages.manage-assignments';

    protected static ?int $navigationSort = 0;

    public int $requiredEmployees = 0;

    public int $assignedEmployees = 0;

    public int $missingEmployees = 0;

    public static function getNavigationLabel(): string
    {
        return __('إسناد الموظفين');
    }

    public static function getPluralLabel(): string
    {
        return __('إسناد الموظفين');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Employee Management');
    }

    public ?int $projectId = null;

    public array $records = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function loadProjectEmployeesFromButton(): void
    {
        if ($this->projectId) {
            $this->loadProjectEmployees($this->projectId);

            Notification::make()
                ->title('✅ تم تحميل الموظفين بنجاح')
                ->success()
                ->send();
        }
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(12)
                ->schema([
                    Select::make('projectId')
                        ->label('اختر المشروع')
                        ->options(Project::pluck('name', 'id'))
                        ->reactive()
                        ->searchable()
                        ->required()
                        ->afterStateUpdated(function (callable $set) {
                            $set('records', []);
                            $this->requiredEmployees = 0;
                            $this->assignedEmployees = 0;
                            $this->missingEmployees = 0;
                        })
                        ->columnSpan(10),

                    Forms\Components\Placeholder::make('load_button')
                        ->content('🔄 تحميل الموظفين')
                        ->extraAttributes([
                            'class' => 'filament-button filament-button-size-md rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition',
                            'style' => 'cursor:pointer; text-align:center;',
                            'wire:click' => 'loadProjectEmployeesFromButton',
                        ])
                        ->visible(fn (callable $get) => $get('projectId')) // يظهر فقط إذا تم اختيار مشروع
                        ->columnSpan(2),
                ]),

            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\Placeholder::make('required_employees')
                        ->label('العدد المطلوب')
                        ->content(fn () => $this->requiredEmployees)
                        ->columnSpan(1),

                    Forms\Components\Placeholder::make('assigned_employees')
                        ->label('الموظفين المسندين')
                        ->content(fn () => $this->assignedEmployees)
                        ->columnSpan(1),

                    Forms\Components\Placeholder::make('missing_employees')
                        ->label('النقص')
                        ->content(fn () => $this->missingEmployees)
                        ->columnSpan(1),
                ])
                ->visible(fn (callable $get) => $get('projectId') !== null),

            Repeater::make('records')
                ->label('الموظفون')
                ->schema([
                    EmployeeSelectV2::make()
                        ->columnSpan(2),

                    Select::make('zone_id')
                        ->label('الموقع')
                        ->options(fn (callable $get) => $get('../../projectId') // استدعاء قيمة projectId من الفورم (وليس من $this)
        ? Zone::where('project_id', $get('../../projectId'))->pluck('name', 'id')
        : []
                        )

                        ->reactive()
                        ->required()
                        ->columnSpan(2),

                    Select::make('shift_id')
                        ->label('الوردية')
                        ->options(fn (callable $get) => $get('zone_id') ? Shift::where('zone_id', $get('zone_id'))->pluck('name', 'id') : []
                        )

                        ->required()
                        ->columnSpan(2),

                    DatePicker::make('start_date')
                        ->label('تاريخ البداية')
                        ->required()
                        ->columnSpan(1),

                    // DatePicker::make('end_date')
                    //     ->label('تاريخ النهاية')
                    //     ->columnSpan(1),
                ])
                ->columns(7) // 👈  توزيع الأعمدة على صف واحد
                ->minItems(1)
                ->default(fn () => $this->records),

        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Pages\Actions\Action::make('save')
                ->label('💾 حفظ التعديلات')
                ->action('save')
                ->color('primary')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('تأكيد الحفظ')
                ->modalDescription('هل أنت متأكد أنك تريد حفظ التعديلات؟')
                ->modalButton('نعم، احفظ الآن'), // ✅ هذا هو المهم
        ];
    }

    public function save(): void
    {
        $created = 0;
        $updated = 0;
        $updatWitLoc = 0;
        $notificationJobs = [];

        DB::transaction(function () use (&$created, &$updated, &$updatWitLoc, &$notificationJobs) {
            // 🔍 جميع التركيبات الحالية: employee_id + zone_id + shift_id
            $existingCombinations = collect($this->records)
                ->filter(fn ($item) => isset($item['employee_id'], $item['zone_id'], $item['shift_id']))
                ->map(fn ($item) => $item['employee_id'].'-'.$item['zone_id'].'-'.$item['shift_id']);

            // 🔍 جلب السجلات القديمة المرتبطة بالمشروع والتي لم تعد موجودة الآن
            $toBeDisabled = EmployeeProjectRecord::where('project_id', $this->projectId)
                ->where('status', true)
                ->get()
                ->filter(function ($record) use ($existingCombinations) {
                    $key = $record->employee_id.'-'.$record->zone_id.'-'.$record->shift_id;

                    return ! $existingCombinations->contains($key);
                });

            // ⛔ تعطيلها فعليًا
            EmployeeProjectRecord::whereIn('id', $toBeDisabled->pluck('id'))
                ->update(['status' => false, 'end_date' => now()]);

            foreach ($toBeDisabled as $record) {
                $notificationJobs[] = [
                    'type' => 'end',
                    'record' => $record,
                ];
            }

            // ✅ المعالجة الأساسية
            foreach ($this->records as $data) {
                // إسناد جديد (لا يحتوي على id)
                if (! isset($data['id'])) {
                    // التأكد من عدم وجود سجل نشط لنفس الموظف + الموقع + الوردية
                    $existing = EmployeeProjectRecord::where('employee_id', $data['employee_id'])
                        ->where('project_id', $this->projectId)
                        ->where('zone_id', $data['zone_id'])
                        ->where('shift_id', $data['shift_id'])
                        ->where('status', true)
                        ->first();

                    if (! $existing) {
                        $createdRecord = EmployeeProjectRecord::create([
                            'employee_id' => $data['employee_id'],
                            'project_id' => $this->projectId,
                            'zone_id' => $data['zone_id'],
                            'shift_id' => $data['shift_id'],
                            'start_date' => $data['start_date'],
                            'end_date' => $data['end_date'] ?? null,
                            'status' => true,
                        ]);
                        $created++;

                        // تأكد من تفعيل الموظف إذا كان غير مفعل
                        $createdRecord->employee->update(['status' => 1]);

                        $notificationJobs[] = [
                            'type' => 'assign',
                            'record' => $createdRecord,
                        ];
                    }

                    continue;
                }

                // ✅ تعديل سجل موجود
                $record = EmployeeProjectRecord::find($data['id']);
                if (! $record) {
                    continue;
                }

                // تم تغيير الموظف
                if ($record->employee_id != $data['employee_id']) {
                    $record->update(['status' => false, 'end_date' => now()->toDateString()]);

                    $newRecord = EmployeeProjectRecord::create([
                        'employee_id' => $data['employee_id'],
                        'project_id' => $this->projectId,
                        'zone_id' => $data['zone_id'],
                        'shift_id' => $data['shift_id'],
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'] ?? null,
                        'status' => true,
                    ]);
                    $updatWitLoc++;

                    $newRecord->employee->update(['status' => 1]);

                    $notificationJobs[] = [
                        'type' => 'transfer_employee',
                        'record' => $newRecord,
                    ];
                }
                // تم تغيير الموقع أو الوردية أو تاريخ البدء
                elseif (
                    $record->zone_id !== $data['zone_id'] ||
                    $record->shift_id !== $data['shift_id'] ||
                    $record->start_date !== $data['start_date']
                ) {
                    $record->update(['status' => false, 'end_date' => now()->toDateString()]);

                    $newRecord = EmployeeProjectRecord::create([
                        'employee_id' => $data['employee_id'],
                        'project_id' => $this->projectId,
                        'zone_id' => $data['zone_id'],
                        'shift_id' => $data['shift_id'],
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'] ?? null,
                        'status' => true,
                    ]);
                    $updatWitLoc++;

                    $newRecord->employee->update(['status' => 1]);

                    $notificationJobs[] = [
                        'type' => 'transfer_location',
                        'record' => $newRecord,
                    ];
                } else {
                    // لم يتم تغيير الموقع أو الموظف أو الوردية
                    $updated++;
                }
            }
        });

        // ✅ تنفيذ الإشعارات بعد نجاح المعاملة
        \App\Services\AssignmentNotifier::dispatchJobs($notificationJobs);

        Notification::make()
            ->title('✅ تم حفظ التعديلات')
            ->body("📌 تم تنفيذ العمليات: {$created} إضافة، {$updated} تحديث، {$updatWitLoc} نقل")
            ->success()
            ->send();

        $this->reset(['projectId', 'records']);
    }

    protected function loadProjectEmployees($projectId): void
    {
        $project = Project::findOrFail($projectId);

        $this->requiredEmployees = $project->emp_no ?? 0;

        $this->records = EmployeeProjectRecord::where('project_id', $projectId)
            ->where('status', true)
            ->get()
            ->map(fn ($record) => [
                'employee_id' => $record->employee_id,
                'zone_id' => $record->zone_id,
                'shift_id' => $record->shift_id,
                'start_date' => $record->start_date,
                'end_date' => $record->end_date,
                'id' => $record->id,
            ])
            ->toArray();

        $this->assignedEmployees = count($this->records);
        $this->missingEmployees = max(0, $this->requiredEmployees - $this->assignedEmployees);
    }

    protected function sendAssignmentNotification(EmployeeProjectRecord $record): void
    {
        $notificationService = new \App\Services\NotificationService;
        $employee = \App\Models\Employee::find($record->employee_id);
        $zone = \App\Models\Zone::find($record->zone_id);
        $project = \App\Models\Project::find($record->project_id);
        $shift = \App\Models\Shift::find($record->shift_id);

        $assignedBy = auth()->user()->name ?? 'نظام';

        if ($employee && $zone && $project && $shift) {
            // إشعار للمسؤولين
            $notificationService->sendNotification(
                ['manager', 'general_manager', 'hr'],
                '📌 إسناد موظف إلى موقع جديد',
                "👤 *اسم الموظف:* {$employee->name()}\n".
                "📌 *الموقع:* {$zone->name} - {$project->name}\n".
                "🕒 *الوردية:* {$shift->name}\n".
                "📅 *تاريخ البدء:* {$record->start_date}\n".
                '📅 *تاريخ الانتهاء:* '.($record->end_date ?? 'غير محدد')."\n\n".
                "🆔 *رقم الهوية:* {$employee->national_id}\n".
                "📞 *الجوال:* {$employee->mobile_number}\n".
                "📢 *تم الإسناد بواسطة:* {$assignedBy}",
                [
                    $notificationService->createAction('عرض الموظف', "/admin/employees/{$employee->id}/view", 'heroicon-s-eye'),
                    $notificationService->createAction('عرض الموقع', "/admin/zones/{$zone->id}", 'heroicon-s-map'),
                ]
            );

            try {
                $otpService = new \App\Services\OtpService;
                $mobileNumber = preg_replace('/^966/', '', $employee->mobile_number);

                $message = "مرحباً {$employee->name()},\n\n";
                $message .= "تم إسنادك إلى موقع جديد:\n";
                $message .= "📍 *{$zone->name}*\n🕒 *{$shift->name}*\n";
                $message .= "📅 *تاريخ البدء:* {$record->start_date}\n";
                $message .= "\n📥 لتحميل التطبيق:\n";
                $message .= "▶️ Android: https://play.google.com/store/apps/details?id=com.intshar.artalapp\n";
                $message .= "🍏 iOS: https://apps.apple.com/us/app/artal/id6740813953\n";
                $message .= "\nشكراً.";

                $otpService->sendOtp($employee->mobile_number, $message);
                $otpService->sendOtp('120363385699307538@g.us', $message);

            } catch (\Exception $e) {
                \Log::error('خطأ أثناء إرسال الرسالة للموظف', [
                    'exception' => $e,
                    'employee_id' => $employee->id,
                    'mobile_number' => $employee->mobile_number,
                ]);
            }
        }
    }
}
