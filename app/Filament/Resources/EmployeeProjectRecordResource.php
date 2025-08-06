<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Zone;
use App\Models\Shift;
use App\Models\Project;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Services\OtpService;
use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use App\Models\EmployeeProjectRecord;
use Filament\Forms\Components\Select;
use App\Tables\Filters\EmployeeFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use App\Forms\Components\EmployeeSelect;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Actions\DeleteBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\EmployeeProjectRecordResource\Pages;
use App\Filament\Resources\EmployeeProjectRecordResource\RelationManagers\ActivityLogsRelationManager;


class EmployeeProjectRecordResource extends Resource
{
    protected static ?string $model = EmployeeProjectRecord::class;

    // navigation icon
    protected static ?string $navigationIcon = 'fluentui-globe-person-20-o';

    public static function getNavigationBadge(): ?string
    {
        // ✅ إخفاء العدد عن المستخدمين غير الإداريين
        if (! auth()->user()?->hasRole('admin')) {
            return null;
        }

        return static::getModel()::count();
    }

    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('Employee Project Records');
    }

    public static function getPluralLabel(): string
    {
        return __('Employee Project Records');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Employee Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            EmployeeSelect::make()
             ->disabled(fn ($livewire) => $livewire instanceof EditRecord),

            // Select::make('employee_id')
            // ->label(__('Employee'))
            // ->searchable()
            // ->getSearchResultsUsing(function (string $search) {
            //     return \App\Models\Employee::query()
            //         ->where('national_id', 'like', "%{$search}%") // البحث باستخدام رقم الهوية
            //         ->orWhere('first_name', 'like', "%{$search}%") // أو البحث باستخدام الاسم
            //         ->limit(50)
            //         ->pluck('first_name', 'id'); // استرجاع الاسم فقط
            // })
            // ->getOptionLabelUsing(function ($value) {
            //     $employee = \App\Models\Employee::find($value);
            //     return $employee ? "{$employee->first_name} {$employee->family_name}" : null; // عرض الاسم فقط
            // })
            // ->required(),

            Select::make('project_id')
                ->label(__('Project'))
                ->options(\App\Models\Project::all()->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->reactive()
                ->afterStateUpdated(function (callable $set) {
                    $set('zone_id', null); // إعادة تعيين اختيار الموقع عند تغيير المشروع
                    $set('shift_id', null); // إعادة تعيين اختيار الوردية عند تغيير المشروع
                })
                ->disabled(fn ($livewire) => $livewire instanceof EditRecord),

            // اختيار الموقع
            Select::make('zone_id')
                ->label(__('Zone'))
                ->options(function (callable $get) {
                    $projectId = $get('project_id');
                    if (! $projectId) {
                        return [];
                    }

                    return \App\Models\Zone::where('project_id', $projectId)->pluck('name', 'id');
                })
                 ->disabled(fn ($livewire) => $livewire instanceof EditRecord)
                ->searchable()
                ->required()
                ->reactive()
                ->afterStateUpdated(function (callable $set) {
                    $set('shift_id', null); // إعادة تعيين اختيار الوردية عند تغيير الموقع
                }),

            // اختيار الوردية
            Select::make('shift_id')
                ->label(__('Shift'))
                ->options(function (callable $get) {
                    $zoneId = $get('zone_id');
                    if (! $zoneId) {
                        return [];
                    }

                    return \App\Models\Shift::where('zone_id', $zoneId)->pluck('name', 'id');
                })
                ->reactive()
                ->searchable()
                ->required()
                ->afterStateUpdated(function ($state, callable $set) {
                    $shift = \App\Models\Shift::find($state);
                    if ($shift) {
                        $start = \Carbon\Carbon::parse($shift->start_date)->toDateString();
                        $today = now('Asia/Riyadh')->toDateString();

                        // إذا اليوم أصغر من بداية الوردية، نضبط التاريخ على بداية الوردية
                        $set('start_date', $today < $start ? $start : $today);
                    }
                }),

            Select::make('shift_slot_id')
                ->label(__('Slot (الشاغر)'))
                ->options(function (callable $get, ?EmployeeProjectRecord $record) {
                    $shiftId = $get('shift_id');
                    if (! $shiftId) return [];

                    // 🔄 الشواغر المتاحة
                    $usedSlots = \App\Models\EmployeeProjectRecord::query()
                        ->where('status', true)
                        ->whereNull('end_date')
                        ->where('shift_id', $shiftId)
                        ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                        ->pluck('shift_slot_id')
                        ->filter()
                        ->toArray();

                    $query = \App\Models\ShiftSlot::where('shift_id', $shiftId)
                        ->when($usedSlots, fn($q) => $q->whereNotIn('id', $usedSlots));

                    // ✅ إضافة الشاغر المختار حتى لو لا يتبع الوردية الحالية
                    if ($record && $record->shift_slot_id) {
                        $query->orWhere('id', $record->shift_slot_id);
                    }

                    // 👁️ عرض معلومات الشاغر بشكل واضح
                    return $query->with('shift')->get()->mapWithKeys(function ($slot) {
                        $label = 'شاغر #' . $slot->slot_number;
                        $label .= ' - وردية: ' . optional($slot->shift)->name;
                        return [$slot->id => $label];
                    });
                })
                ->searchable()
                ->required()
                ->visible(fn(callable $get) => $get('shift_id'))
                ->helperText('اختر شاغر تابع للوردية المحددة')
                ->reactive()
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    $shiftId = $get('shift_id');
                    $slot = \App\Models\ShiftSlot::with('shift')->find($state);

                    // ⚠️ تحذير إذا كان الشاغر لا يتبع الوردية الحالية
                    if ($slot && $slot->shift_id != $shiftId) {
                        Notification::make()
                            ->title('⚠️ الشاغر لا يتبع الوردية المحددة')
                            ->body('يرجى التأكد من اختيار شاغر صحيح')
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),



            DatePicker::make('start_date')
                ->label(__('Start Date'))
                // ->minDate(fn(callable $get) => \App\Models\Shift::find($get('shift_id'))?->start_date)

                ->required(),

            DatePicker::make('end_date')
                ->label(__('End Date')),

          Forms\Components\Toggle::make('status')
    ->label(__('Status'))
    ->onColor('success')
    ->offColor('danger')
    ->required()
    ->default(true)

    /* 1️⃣ أخفِ المفتاح تمامًا إذا كان السجل موجودًا وحالته مُعطَّلة */
    ->visible(fn (?EmployeeProjectRecord $rec) =>
        ! ($rec && (int) $rec->status === 0)
    )

    /* 2️⃣ لا تُرسِل الحقل مع البيانات عند التعطيل */
    ->dehydrated(fn (?EmployeeProjectRecord $rec) =>
        ! ($rec && (int) $rec->status === 0)
    )

    /* 3️⃣ عند التبديل من مفعَّل → مُعطَّل نفّذ الآتى */
    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {

        /* إذا أصبح مُعطَّلاً */
        if ((int) $state === 0) {

            /* ▸ أ. ضَبْط end_date إن كانت فارغة */
            if (empty($get('end_date'))) {
                $set('end_date', now('Asia/Riyadh')->toDateString());
            }

           
        }
    })

    /* (اختياري) رسالة توضّح سبب إخفاء الحقل */
    ->helperText(fn (?EmployeeProjectRecord $rec) =>
        $rec && (int) $rec->status === 0
            ? '🛑 هذا الإسناد مُعطَّل نهائيًا ولا يمكن إعادة تفعيله.'
            : null
    ),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('employee.first_name')
                //     ->label(__('Employee'))
                //     ->sortable()
                //     ->searchable(),
                TextColumn::make('full_name')
                    ->label(__('Employee'))
                    ->getStateUsing(
                        fn($record) => $record->employee->first_name . ' ' .
                            $record->employee->father_name . ' ' .
                            $record->employee->grandfather_name . ' ' .
                            $record->employee->family_name
                    )
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('employee', function ($subQuery) use ($search) {
                            $subQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('father_name', 'like', "%{$search}%")
                                ->orWhere('grandfather_name', 'like', "%{$search}%")
                                ->orWhere('family_name', 'like', "%{$search}%")
                                ->orWhere('national_id', 'like', "%{$search}%");
                        });
                    })
                    ->copyable()
                    ->copyMessageDuration(1500),
                TextColumn::make('employee.national_id')
                    ->label(__('National ID'))
                    ->searchable()
                    ->copyable()
                    ->copyMessageDuration(1500),

                TextColumn::make('project.name')
                    ->label(__('Project'))
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessageDuration(1500),

                TextColumn::make('zone.name')
                    ->label(__('Zone'))
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessageDuration(1500),
                TextColumn::make('shift.name')
                    ->label(__('Shift'))
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessageDuration(1500),

                TextColumn::make('start_date')
                    ->label(__('Start Date'))
                    ->date(),

                TextColumn::make('end_date')
                    ->label(__('End Date'))
                    ->sortable()
                    ->date(),
                BooleanColumn::make('status')
                    ->label(__('Status'))
                    ->sortable(),
                TextColumn::make('previous_month_attendance')
                    ->label('دوام الشهر الماضي')
                    ->getStateUsing(fn($record) => self::getPreviousMonthAttendance($record))
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('work_pattern')
                    ->label('نمط العمل')
                    ->getStateUsing(fn($record) => self::calculateWorkPattern($record))
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assignedBy.name')
                    ->label(__('Assigned By'))
                    ->toggleable(isToggledHiddenByDefault: true), // مخفي افتراضيًا

                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('slot_issue_type')
                    ->label('🛠️ حالة الشاغر')
                    ->getStateUsing(function ($record) {
                        if (is_null($record->shift_slot_id)) {
                            return '❌ بدون شاغر';
                        }

                        if ($record->shiftSlot?->shift_id != $record->shift_id) {
                            return '❌ الشاغر لا يتبع الوردية';
                        }

                        $count = \App\Models\EmployeeProjectRecord::query()
                            ->where('status', true)
                            ->whereNull('end_date')
                            ->where('shift_slot_id', $record->shift_slot_id)
                            ->count();

                        if ($count > 1) {
                            return "⚠️ مكرر ($count)";
                        }

                        return '✅ سليم';
                    })
                    ->badge()
                    ->color(fn($state) => match (true) {
                        str_contains($state, '❌') => 'danger',
                        str_contains($state, '⚠️') => 'warning',
                        default => 'success',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),


            ])

            // ->persistSearchInSession()
            // ->persistColumnSearchesInSession()
            ->filters([

                Filter::make('slot_issues')
                    ->label('⚠️ أخطاء الشواغر')
                    ->query(function (Builder $query) {
                        $query->where(function ($q) {
                            $q->whereNull('shift_slot_id') // بدون شاغر
                                ->orWhereHas(
                                    'shiftSlot',
                                    fn($slotQ) =>
                                    $slotQ->whereColumn('shift_slots.shift_id', '!=', 'employee_project_records.shift_id') // شاغر لا يتبع وردية
                                )
                                ->orWhereIn('shift_slot_id', function ($sub) {
                                    $sub->select('shift_slot_id')
                                        ->from('employee_project_records')
                                        ->whereNotNull('shift_slot_id')
                                        ->where('status', true)
                                        ->whereNull('end_date')
                                        ->groupBy('shift_slot_id')
                                        ->havingRaw('COUNT(*) > 1');
                                });
                        });
                    })
                    ->indicator('⚠️ فقط الأخطاء'),
                SelectFilter::make('project_id')
                    ->label(__('Project'))
                    ->options(Project::all()->pluck('name', 'id'))
                    ->searchable()
                    ->multiple(),

                SelectFilter::make('zone_id')
                    ->label(__('Zone'))
                    ->options(Zone::all()->pluck('name', 'id'))
                    ->searchable()
                    ->multiple(),

                Filter::make('multi_active_assignments')
                    ->label('موظفون لديهم أكثر من إسناد نشط')
                    ->query(function (Builder $query) {
                        // أولًا نحصل على معرّفات الموظفين اللي عندهم أكثر من إسناد نشط
                        $employeeIds = EmployeeProjectRecord::select('employee_id')
                            ->where('status', true)
                            ->groupBy('employee_id')
                            ->havingRaw('COUNT(*) > 1')
                            ->pluck('employee_id');

                        // ثم نعرض فقط السجلات اللي تخصهم
                        $query->whereIn('employee_id', $employeeIds);
                    })
                    ->indicator('إسنادات نشطة متعددة'),

                // SelectFilter::make('employee_id')
                //     ->label(__('Employee'))
                //     ->options(Employee::all()->pluck('first_name', 'id')),
                EmployeeFilter::make('employee_filter'),

                TernaryFilter::make('status')
                    ->label(__('Status'))
                    ->nullable(),
            ])
            ->actions([

                Action::make('replace_employee')
                    ->visible(fn() => auth()->user()?->can('create_employee::project::record'))

                    ->label('استبدال الموظف')
                    // ->icon('heroicon-o-user-switch')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        // Select::make('new_employee_id')
                        //     ->label('اختر الموظف البديل')
                        //     ->options(Employee::all()->mapWithKeys(function ($employee) {
                        //         return [$employee->id => $employee->name ?? 'موظف غير معروف'];
                        //     }))
                        //     ->searchable()
                        //     ->required(),
                        EmployeeSelect::make('new_employee_id')
                            ->label('اختر الموظف البديل')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $newEmployeeId = $data['new_employee_id'];
                            $currentDate = now()->toDateString();

                            // ✅ تحديث السجل القديم بإضافة تاريخ نهاية
                            $record->update([
                                'end_date' => $currentDate,
                                'status' => false, // تعطيل السجل القديم
                            ]);

                            // ✅ إنشاء سجل جديد بنفس بيانات الموقع والوردية
                            EmployeeProjectRecord::create([
                                'employee_id' => $newEmployeeId,
                                'project_id' => $record->project_id,
                                'zone_id' => $record->zone_id,
                                'shift_id' => $record->shift_id,
                                'shift_slot_id' => $record->shift_slot_id, // 🔁 نفس الشاغر

                                'start_date' => $currentDate,
                                'status' => true, // تنشيط السجل الجديد
                            ]);

                            // ✅ إرسال إشعار إلى الموظف الجديد
                            $newEmployee = Employee::find($newEmployeeId);
                            // Notification::make()
                            //     ->title('📢 تم إسنادك إلى موقع جديد')
                            //     ->success()
                            //     ->body("📌 تم إسنادك إلى موقع **{$record->zone->name}** ضمن الوردية **{$record->shift->name}** ابتداءً من اليوم.");
                            // ->sendToDatabase($newEmployee)
                        });

                        Notification::make()
                            ->title('✅ تم استبدال الموظف بنجاح')
                            ->success()
                            ->body("تم استبدال الموظف **{$record->employee->full_name}** بموظف جديد.")
                            ->send();
                    }),
                Action::make('print')
                    ->label(__('Print Contract'))
                    ->icon('heroicon-o-printer')
                    ->url(fn($record) => route('employee_project_record.pdf', $record)) // إعادة توجيه إلى رابط PDF
                    ->color('primary'),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('resendMessage')
                    ->label('إعادة إرسال الرسالة')
                    ->action(function ($record) {
                        // استرجاع بيانات الموظف والموقع والوردية
                        $employee = Employee::find($record->employee_id);
                        $zone = Zone::find($record->zone_id);
                        $project = Project::find($record->project_id);
                        $shift = Shift::find($record->shift_id);

                        if ($employee && $zone) {
                            try {
                                $otpService = new OtpService;
                                // إزالة بادئة الدولة من رقم الجوال إذا كانت موجودة
                                $mobileNumber = preg_replace('/^966/', '', $employee->mobile_number);

                                // تحضير نص الرسالة
                                $message = "مرحباً {$employee->name()},\n\n";
                                $message .= "تم إسنادك إلى موقع جديد في النظام. تفاصيل حسابك:\n";
                                $message .= "📌 *اسم المستخدم:* {$mobileNumber}\n";
                                $message .= "🔑 *كلمة المرور:* {$employee->password}\n";
                                $message .= "📍 *الموقع:* {$zone->name}\n\n";

                                $message .= "📥 *لتحميل التطبيق:* \n";
                                $message .= "▶️ *Android:* [Google Play](https://play.google.com/store/apps/details?id=com.intshar.artalapp)\n";
                                $message .= "🍏 *iOS:* [App Store](https://apps.apple.com/us/app/artal/id6740813953)\n\n";
                                $message .= 'شكراً.';

                                // إرسال الرسالة
                                $otpService->sendOtp($employee->mobile_number, $message);
                                $otpService->sendOtp('120363385699307538@g.us', $message);

                                Notification::make()
                                    ->title('✅ تم إرسال الرسالة')
                                    ->success()
                                    ->body("تم إعادة إرسال الرسالة إلى {$employee->name()}.")
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('❌ خطأ')
                                    ->danger()
                                    ->body('حدث خطأ أثناء إعادة إرسال الرسالة: ' . $e->getMessage())
                                    ->send();
                            }
                        }
                    })
                    ->requiresConfirmation() // لتأكيد الإجراء قبل التنفيذ
                    ->color('primary'),

                Action::make('send_location')
                    ->label('📍 إرسال الموقع')
                    ->icon('heroicon-o-map-pin')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد الإرسال')
                    ->modalDescription('هل تريد إرسال موقع الموظف والوردية عبر واتساب؟')
                    ->action(function ($record) {
                        $zone = $record->zone;
                        $shift = $record->shift;
                        $employee = $record->employee;

                        if (! $zone || ! $shift || ! $employee) {
                            Notification::make()
                                ->title('❌ البيانات غير مكتملة')
                                ->body('لم يتم العثور على معلومات الموقع أو الوردية أو الموظف.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $googleMapsUrl = "https://www.google.com/maps?q={$zone->lat},{$zone->longg}";

                        $message = "👷‍♂️ الموظف: {$employee->name()}\n";
                        $message .= "📌 الموقع: {$zone->name}\n";
                        $message .= "🕒 الوردية: {$shift->name}\n";
                        $message .= "📍 الموقع على الخريطة:\n{$googleMapsUrl}\n\n";
                        $message .= "📞 بيانات الاتصال:\n";
                        $message .= "- قسم الموارد البشرية: hradmin@artalgroup.net | 0581797003\n";
                        $message .= "- العمليات: 0595639648\n";
                        $message .= '- الدعم الفني: 0593222287';

                        try {
                            $otpService = new OtpService;

                            // إرسال للموظف
                            $otpService->sendOtp($employee->mobile_number, $message);

                            // إرسال للجروب الإداري
                            $otpService->sendOtp('120363385699307538@g.us', $message);

                            Notification::make()
                                ->title('✅ تم إرسال الموقع')
                                ->body('تم إرسال الموقع إلى الموظف والجروب بنجاح.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ خطأ في الإرسال')
                                ->body('حدث خطأ أثناء إرسال الموقع: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

            ])
            ->paginationPageOptions([10, 25, 50, 100])
            ->bulkActions([
                DeleteBulkAction::make(),
                ExportBulkAction::make(),

            ]);
    }

    //    protected function mutateFormDataBeforeSave(array $data): array
    // {
    //     if (
    //         isset($data['status']) &&
    //         $this->record &&
    //         $this->record->status === true &&   // كان نشطًا
    //         $data['status'] === false &&        // أصبح غير نشط
    //         empty($data['end_date'])            // لا يوجد end_date
    //     ) {
    //         $data['end_date'] = now('Asia/Riyadh');
    //     }

    //     return $data;
    // }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeProjectRecords::route('/'),
            'create' => Pages\CreateEmployeeProjectRecord::route('/create'),
            'edit' => Pages\EditEmployeeProjectRecord::route('/{record}/edit'),
        ];
    }


    public static function getRelations(): array
    {
        return [
            ActivityLogsRelationManager::class,
        ];
    }

    // private static function calculateWorkPattern($record)
    // {
    //     $pattern = $record->shift->zone->pattern ?? null;

    //     if (! $pattern) {
    //         return '<span style="color: red;">❌ لا يوجد نمط محدد</span>';
    //     }

    //     $workingDays = $pattern->working_days;
    //     $offDays = $pattern->off_days;
    //     $cycleLength = $workingDays + $offDays;

    //     $startDate = Carbon::parse($record->start_date);
    //     $currentDate = Carbon::now('Asia/Riyadh');
    //     $totalDays = $currentDate->diffInDays($startDate);
    //     $currentDayInCycle = $totalDays % $cycleLength;

    //     $cycleNumber = (int) floor($totalDays / $cycleLength) + 1; // حساب رقم الدورة الحالية

    //     $daysView = [];

    //     for ($i = 0; $i < 30; $i++) {
    //         $dayInCycle = ($currentDayInCycle + $i) % $cycleLength;
    //         $isWorkDay = $dayInCycle < $workingDays;
    //         $date = $currentDate->copy()->addDays($i)->format('d M');

    //         $color = $isWorkDay ? 'green' : 'red';
    //         $label = $isWorkDay ? '' : '';

    //         // ✅ إضافة "صباحًا" أو "مساءً" بجانب أيام العمل
    //         if ($isWorkDay) {
    //             $shiftType = ($cycleNumber % 2 == 1) ? 'ص' : 'م';
    //             $label .= " - $shiftType";
    //         }

    //         // $daysView[] = "<span style='padding: 4px; border-radius: 5px; background-color: $color; color: white; margin-right: 5px;'>$date: $label</span>";
    //         $daysView[] = "
    //         <span style='
    //             padding: 4px;
    //             border-radius: 5px;
    //             background-color: $color;
    //             color: white;
    //             display: inline-block;
    //             width: 110px; /* ضمان نفس العرض */
    //              height: 30px;
    //              margin-bottom: 0px; /* تقليل الهوامش بين الصفوف */

    //             text-align: center;
    //             margin-right: 5px;
    //             font-weight: bold;
    //         '>
    //             $date$label
    //         </span>";
    //     }

    //     return implode(' ', $daysView);
    // }
    public static function calculateWorkPattern($record)
    {
        if (! $record->shift || ! $record->shift->zone || ! $record->shift->zone->pattern) {
            return '<span style="color: red; font-weight: bold; padding: 4px; display: inline-block; width: 100px; text-align: center;">❌ غير متوفر</span>';
        }

        $pattern = $record->shift->zone->pattern;
        $workingDays = (int) $pattern->working_days;
        $offDays = (int) $pattern->off_days;
        $cycleLength = $workingDays + $offDays;

        // ✅ حساب بداية الدورة من `shift.start_date`
        $startDate = Carbon::parse($record->shift->start_date);
        $assignStart = \Carbon\Carbon::parse($record->start_date);

        $currentDate = Carbon::now('Asia/Riyadh');

        $daysView = [];

        for ($i = 0; $i < 30; $i++) {
            $targetDate = $currentDate->copy()->addDays($i); // ✅ تحديد تاريخ الخلية


            $recordStart = Carbon::parse($record->start_date);
            $shiftStart = Carbon::parse($record->shift->start_date);
            // $minStart = $recordStart->greaterThan($shiftStart) ? $recordStart : $shiftStart;
            $displayDate = $targetDate->format('d M');

            // ❗️إذا التاريخ قبل بداية الوردية أو بداية الإسناد
            if ($targetDate->lt($shiftStart) || $targetDate->lt($assignStart)) {
                $daysView[] = "
            <span style='
                padding: 4px;
                border-radius: 5px;
                background-color: #9E9E9E; /* رمادي غامق */
                color: white;
                display: inline-block;
                width: 110px;
                height: 30px;
                margin-bottom: 0px;
                text-align: center;
                margin-right: 5px;
                font-weight: bold;
            '>
                $displayDate - 🕒
            </span>";
                continue;
            }
            $totalDays = $startDate->diffInDays($targetDate); // ✅ حساب الفرق من بداية الوردية وليس من اليوم الحالي

            // ✅ حساب اليوم داخل الدورة بناءً على `totalDays`
            $currentDayInCycle = $totalDays % $cycleLength;
            $cycleNumber = (int) floor($totalDays / $cycleLength) + 1; // ✅ حساب الدورة الزمنية الصحيحة

            // ✅ تحديد إذا كان اليوم "عمل" أو "إجازة" بناءً على `workingDays`
            $isWorkDay = $currentDayInCycle < $workingDays;
            $date = $targetDate->format('d M');

            $color = $isWorkDay ? 'green' : 'red';
            $label = $isWorkDay ? '' : '';

            // ✅ تحديد الفترة "صباحًا" أو "مساءً" فقط إذا كان يوم عمل
            if ($isWorkDay) {
                $shiftType = ($cycleNumber % 2 == 1) ? 'ص' : 'م';
                switch ($record->shift->type) {
                    case 'morning':
                        $shiftType = 'ص';
                        break;

                    case 'evening':
                        $shiftType = 'م';
                        break;

                    case 'morning_evening':
                        // $shiftType = 'ص';
                        break;

                    case 'evening_morning':
                        $shiftType = ($cycleNumber % 2 == 1) ? 'م' : 'ص';
                        break;
                }
                $label .= " - $shiftType";
            }

            // ✅ تحسين التنسيق وتقليل الهوامش بين العناصر
            $daysView[] = "
             <span style='
                padding: 4px;
                border-radius: 5px;
                background-color: $color;
                color: white;
                display: inline-block;
                width: 110px; /* ضمان نفس العرض */
                 height: 30px;
                 margin-bottom: 0px; /* تقليل الهوامش بين الصفوف */

                text-align: center;
                margin-right: 5px;
                font-weight: bold;
            '>
                $date$label
            </span>";
        }

        return implode(' ', $daysView);
    }

    private static function getPreviousMonthAttendance($record)
    {
        $employeeId = $record->employee_id;
        $currentDate = Carbon::now('Asia/Riyadh');
        $startDate = $currentDate->copy()->subDays(30)->format('Y-m-d');
        $endDate = $currentDate->format('Y-m-d');

        // جلب بيانات الحضور للموظف خلال آخر 30 يومًا
        $attendances = \App\Models\Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy('date'); // تحويل النتيجة إلى مصفوفة تعتمد على التاريخ

        // خريطة الألوان الجديدة لكل حالة
        $attendanceColors = [
            'present' => '#2E7D32',  // أخضر غامق
            'absent' => '#D32F2F',   // أحمر غامق
            'coverage' => '#F9A825', // أصفر برتقالي
            'M' => '#E91E63',        // وردي غامق
            'leave' => '#388E3C',    // أخضر غامق
            'UV' => '#F57C00',       // برتقالي غامق
            'W' => '#795548',        // بني غامق
            'N/A' => '#9E9E9E',      // رمادي غامق
        ];

        $daysView = [];

        for ($i = 30; $i >= 1; $i--) {
            $date = $currentDate->copy()->subDays($i)->format('Y-m-d');
            $displayDate = $currentDate->copy()->subDays($i)->format('d M');

            $attendance = $attendances[$date] ?? null;
            $status = $attendance ? $attendance->status : 'N/A';
            $color = $attendanceColors[$status] ?? '#9E9E9E'; // إذا لم يكن هناك لون، استخدم الرمادي

            $daysView[] = "
            <span style='
                padding: 6px; 
                border-radius: 5px; 
                background-color: $color; 
                color: white; 
                display: inline-block; 
                width: 120px; /* ضمان نفس العرض */
                height: 30px;
                text-align: center; 
                border: 1px solid black; /* إضافة حد أسود */
                margin-right: 5px; 
                font-weight: bold;
            '>
                $displayDate - $status
            </span>";
        }

        return implode(' ', $daysView);
    }
}
