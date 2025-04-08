<?php

namespace App\Filament\Pages;

use App\Forms\Components\EmployeeSelect;
use App\Models\EmployeeProjectRecord;
use App\Models\Project;
use App\Models\Shift;
use App\Models\Zone;
use App\Services\NotificationService;
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
                        ->afterStateUpdated(fn (callable $set) => $set('records', []))
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

            Repeater::make('records')
                ->label('الموظفون')
                ->schema([
                    EmployeeSelect::make()
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
        foreach ($this->records as $item) {
            if (! isset($item['employee_id'], $item['zone_id'], $item['shift_id'], $item['start_date'])) {
                Notification::make()
                    ->title('⚠️ تأكد من إدخال جميع البيانات لكل موظف')
                    ->danger()
                    ->send();

                return;
            }
        }

        $created = 0;
        $updated = 0;
        $updatWitLoc = 0;

        DB::transaction(function () use (&$created, &$updated, &$updatWitLoc) {
            $existingIds = collect($this->records)->pluck('employee_id')->filter();

            // تعطيل الموظفين الذين لم يعودوا ضمن القائمة الجديدة
            EmployeeProjectRecord::where('project_id', $this->projectId)
                ->whereNotIn('employee_id', $existingIds)
                ->update(['status' => false, 'end_date' => now()]);
            // dd($this->records);
            foreach ($this->records as $data) {
                // $record = EmployeeProjectRecord::firstWhere([
                //     'employee_id' => $data['employee_id'],
                //     'project_id' => $this->projectId,
                // ]);
                $record = null;
                if (! empty($data['id'])) {
                    $record = EmployeeProjectRecord::find($data['id']);
                }

                if ($record) {
                    $hasChanged = $record->zone_id !== $data['zone_id']
                        || $record->shift_id !== $data['shift_id']
                        || $record->start_date !== $data['start_date'];

                    if ($hasChanged) {
                        // تعطيل السجل القديم
                        $record->update([
                            'status' => false,
                            'end_date' => now()->toDateString(),
                        ]);

                        // إنشاء سجل جديد
                        $newRecord = EmployeeProjectRecord::create([
                            'employee_id' => $data['employee_id'],
                            'project_id' => $this->projectId,
                            'zone_id' => $data['zone_id'],
                            'shift_id' => $data['shift_id'],
                            'start_date' => $data['start_date'],
                            'end_date' => $data['end_date'] ?? null,
                            'status' => true,
                        ]);

                        $employee = \App\Models\Employee::find($data['employee_id']);
                        $zone = \App\Models\Zone::find($data['zone_id']);
                        $shift = \App\Models\Shift::find($data['shift_id']);
                        $project = \App\Models\Project::find($this->projectId);
                        $assignedBy = auth()->user()?->name ?? 'نظام';

                        // ✅ إشعار داخلي للمسؤولين
                        $notificationService = new NotificationService;
                        $notificationService->sendNotification(
                            ['manager', 'general_manager', 'hr'],
                            '📌 نقل موظف إلى موقع جديد',
                            "👤 *اسم الموظف:* {$employee->name()}\n".
                            "📌 *الموقع الجديد:* {$zone->name} - {$project->name}\n".
                            "🕒 *الوردية:* {$shift->name}\n".
                            "📅 *تاريخ البدء:* {$newRecord->start_date}\n".
                            '📅 *تاريخ الانتهاء:* '.($newRecord->end_date ?? 'غير محدد')."\n\n".
                            "🆔 *رقم الهوية:* {$employee->national_id}\n".
                            "📞 *الجوال:* {$employee->mobile_number}\n".
                            "📢 *تم النقل بواسطة:* {$assignedBy}",
                            [
                                $notificationService->createAction('عرض الموظف', "/admin/employees/{$employee->id}/view", 'heroicon-s-eye'),
                                $notificationService->createAction('عرض الموقع', "/admin/zones/{$zone->id}", 'heroicon-s-map'),
                            ]
                        );

                        $updatWitLoc++;
                    } else {
                        // لا تغيير كبير، فقط تعديل تواريخ مثل end_date
                        // $record->update([
                        //     'end_date' => $data['end_date'] ?? null,
                        //     'status' => true,
                        // ]);

                        $updated++;
                    }
                } else {
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
                    $this->sendAssignmentNotification($createdRecord); // ✅ إرسال إشعار بعد الإضافة

                }

            }
        });

        Notification::make()
            ->title('✅ تم حفظ التعديلات')
            ->body("📌 تم  موظف، إضافة {$created} موظف جديد ,{$updatWitLoc} نقل")
            ->success()
            ->send();
    }

    protected function loadProjectEmployees($projectId): void
    {
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
                $message .= "🍏 iOS: https://testflight.apple.com/join/Md5YzFE7\n";
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
