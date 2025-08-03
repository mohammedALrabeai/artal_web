<?php

namespace App\Filament\Resources;

use App\Enums\CoverageReason;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Forms\Components\EmployeeSelect;
use App\Models\ApprovalFlow;
use App\Models\Attendance;
use App\Models\Coverage;
use App\Models\Request;
use App\Models\Role;
use App\Models\Shift;
use App\Tables\Filters\EmployeeFilter;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'fluentui-calendar-edit-16-o';

    public static function getNavigationBadge(): ?string
    {
        // ✅ إخفاء العدد عن المستخدمين غير الإداريين
        if (! auth()->user()?->hasRole('admin')) {
            return null;
        }

        return static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return __('Attendances');
    }

    public static function getPluralLabel(): string
    {
        return __('Attendances');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Employee Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            EmployeeSelect::make(),

            Forms\Components\DatePicker::make('date')
                ->label(__('Date'))
                ->required(),

            // Forms\Components\Select::make('zone_id')
            // ->label(__('Zone'))
            // ->options(\App\Models\Zone::all()->pluck('name', 'id'))
            // ->searchable()
            // ->required(),
            // Forms\Components\Select::make('shift_id')
            // ->label(__('Shift'))
            // ->relationship('shift', 'name')
            // ->required(),

            // اختيار الموقع
            Select::make('zone_id')
                ->label(__('Zone'))
                ->options(\App\Models\Zone::all()->pluck('name', 'id'))

            // ->options(function (callable $get) {
            //     $projectId = $get('project_id');
            //     if (!$projectId) {
            //         return [];
            //     }
            //     return \App\Models\Zone::where('project_id', $projectId)->pluck('name', 'id');
            // })
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
                ->searchable()
                ->required(),
            Forms\Components\Select::make('ismorning')
                ->label(__('Time of Day')) // يمكن تغيير النص حسب الحاجة
                ->options([
                    true => __('Morning'),  // صباحًا
                    false => __('Evening'), // مساءً
                ])
                ->nullable() // للسماح بالقيمة الافتراضية null
                ->default(null) // إذا كنت تريد قيمة افتراضية
                ->required(),

            Forms\Components\TimePicker::make('check_in')
                ->label(__('Check In')),
            Forms\Components\DateTimePicker::make('check_in_datetime')
                ->label(__('Check In Datetime'))
                ->required(false),
            Forms\Components\TimePicker::make('check_out')
                ->label(__('Check Out')),
            Forms\Components\DateTimePicker::make('check_out_datetime')
                ->label(__('Check Out Datetime'))
                ->required(false),

            Forms\Components\Select::make('status')
                ->label(__('Status'))
                ->options([
                    'off' => __('Off'),    // إضافة خيار عطلة
                    'present' => __('Present'),   // إضافة خيار الحضور
                    'coverage' => __('Coverage'), // إضافة خيار التغطية
                    'M' => __('Morbid'),  // إضافة خيار مرضي Sick
                    'leave' => __('paid leave'),     // إضافة خيار الإجازة
                    'UV' => __('Unpaid leave'),
                    'absent' => __('Absent'),

                ])
                ->required(),

            // ساعات العمل
            Forms\Components\TextInput::make('work_hours')
                ->label(__('Work Hours'))
                ->numeric()
                ->required(false),

            // ملاحظات الموظف
            Forms\Components\Textarea::make('notes')
                ->label(__('Notes'))
                ->rows(3),

            // تسجيل ما إذا كان الموظف متأخرًا أم لا
            Forms\Components\Checkbox::make('is_late')
                ->label(__('Is Late')),

            Forms\Components\Toggle::make('is_coverage')
                ->label(__('Coverage Request')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('full_name')
                ->label(__('Employee'))
                ->getStateUsing(fn ($record) => $record->employee->first_name.' '.
                    $record->employee->father_name.' '.
                    $record->employee->grandfather_name.' '.
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
                }),

            Tables\Columns\TextColumn::make('employee.national_id')
                ->label(__('National ID'))
                ->searchable(),

            Tables\Columns\TextColumn::make('date')
                ->label(__('Date'))
                ->sortable(),

            Tables\Columns\TextColumn::make('zone.name')
                ->label(__('Zone'))
                ->searchable(),
            Tables\Columns\TextColumn::make('shift.name')
                ->label(__('Shift'))
                ->searchable(),
            Tables\Columns\TextColumn::make('ismorning')
                ->label(__('Time of Day'))
                ->formatStateUsing(function ($state) {
                    if (is_null($state)) {
                        return __(''); // عرض فارغ إذا كانت القيمة null
                    }

                    return $state ? __('Morning') : __('Evening'); // صباحي أو مسائي
                }),

            Tables\Columns\TextColumn::make('check_in')
                ->label(__('Check In')),

            Tables\Columns\TextColumn::make('check_in_datetime')
                ->label(__('Check In Datetime'))
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('check_out')
                ->label(__('Check Out')),

            Tables\Columns\TextColumn::make('status')
                ->label(__('Status'))
                ->badge()
                ->getStateUsing(function ($record) {
                    return match ($record->status) {
                        'off' => __('OFF'),
                        'present' => __('Present'),
                        'coverage' => __('Coverage'),
                        'M' => __('Morbid'),
                        'leave' => __('Paid Leave'),
                       
                        'UV' => __('Unpaid Leave'),
                        'absent' => __('Absent'),

                        //  'PV' => __('Paid Leave'),
                        //  'SL' => __('Sick Leave'),
                        //  'UL' => __('UL'),
                        default => __($record->status),
                    };
                })
                ->colors([
                    'success' => fn ($state) => $state === __('OFF'), // أخضر
                    'primary' => fn ($state) => $state === __('Present'), // أزرق فاتح
                    'warning' => fn ($state) => $state === __('Coverage'), // برتقالي
                    'secondary' => fn ($state) => $state === __('Morbid'), // رمادي
                    'blue-dark' => fn ($state) => $state === __('Paid Leave'), // أزرق غامق
                    // 'blue-dark' => fn ($state) => $state === __('Paid Vacation'), // أزرق
                    'orange-dark' => fn ($state) => $state === __('Unpaid Leave'), // برتقالي غامق
                    'danger' => fn ($state) => $state === __('Absent'), // أحمر
                ]),

            Tables\Columns\TextColumn::make('work_hours')
                ->label(__('Work Hours')),

            Tables\Columns\TextColumn::make('notes')
                ->label(__('Notes')),

            Tables\Columns\BadgeColumn::make('is_late')
                ->label(__('Is Late'))
                ->getStateUsing(fn ($record) => $record->is_late ? __('Yes') : __('No'))
                ->colors([
                    'danger' => fn ($state) => $state === __('Yes'),
                    'success' => fn ($state) => $state === __('No'),
                ]),
            Tables\Columns\BadgeColumn::make('approval_status')
                ->label(__('Approval Status'))
                ->formatStateUsing(fn (string $state): string => ucfirst($state)) // تنسيق النص
                ->colors([
                    'pending' => 'warning',
                    'submitted' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                ]),

            Tables\Columns\BooleanColumn::make('is_coverage')
                ->label(__('Coverage Request')),

            Tables\Columns\TextColumn::make('created_at')
                ->label(__('Created At'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
                ->label(__('Updated At'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
            ->paginationPageOptions([10, 25, 50, 100])
            ->filters([

                Tables\Filters\Filter::make('present_status')
                    ->query(fn (Builder $query) => $query->where('status', 'present'))
                    ->label(__('Present')),
                EmployeeFilter::make('employee_filter'),

                // فلتر المنطقة
                SelectFilter::make('zone_id')
                    ->label(__('Zone'))
                    ->options(\App\Models\Zone::query()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->multiple(),

                SelectFilter::make('shift_id')
                    ->label('Shift')
                    ->options(Shift::all()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->multiple(),

                // SelectFilter::make('ismorning')
                //     ->options([
                //         true => 'صباح',
                //         false => 'مساء',
                //     ]),

                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'off' => __('Off'),    // إضافة خيار عطلة
                        'present' => __('Present'),   // إضافة خيار الحضور
                        'coverage' => __('Coverage'), // إضافة خيار التغطية
                        'M' => __('Morbid'),  // إضافة خيار مرضي Sick
                        'leave' => __('paid leave'),     // إضافة خيار الإجازة
                        'UV' => __('Unpaid leave'),
                        'absent' => __('Absent'),
                    ]),
                // فلتر الحالة

                // فلتر الموظف
                // SelectFilter::make('employee_id')
                //     ->label(__('Employee'))
                //     ->options(\App\Models\Employee::query()->pluck('first_name', 'id')->toArray())
                //     ->searchable(),

                // فلتر التاريخ
                Filter::make('date_range')
                    ->label(__('Date Range'))
                    ->form([
                        Forms\Components\DatePicker::make('from')->label(__('From')),
                        Forms\Components\DatePicker::make('to')->label(__('To')),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, $data) {
                        if (! empty($data['from'])) {
                            $query->where('date', '>=', $data['from']);
                        }
                        if (! empty($data['to'])) {
                            $query->where('date', '<=', $data['to']);
                        }
                    }),

            ])
            ->actions([
                Tables\Actions\Action::make('Approve')
                    ->label(__('Approve'))
                    ->form([
                        // اختيار سبب التغطية
                        Forms\Components\Select::make('coverage_reason')
                            ->label(__('Coverage Reason'))
                            ->options(CoverageReason::labels())
                            ->required()
                            ->reactive(),

                        // اختيار الموظف البديل إذا كان السبب يتطلب ذلك
                        // Forms\Components\Select::make('absent_employee_id')
                        //     ->label(__('Select Replacement Employee'))
                        //     ->options(\App\Models\Employee::pluck('first_name', 'id'))
                        //     ->searchable()
                        //     ->required(fn ($get) => CoverageReason::tryFrom($get('coverage_reason'))?->requiresReplacement() ?? false)
                        //     ->hidden(fn ($get) => ! CoverageReason::tryFrom($get('coverage_reason'))?->requiresReplacement()),
                        EmployeeSelect::make('absent_employee_id')
                            ->hidden(fn ($get) => ! CoverageReason::tryFrom($get('coverage_reason'))?->requiresReplacement()),
                        // ملاحظات إضافية
                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes'))
                            ->nullable(),
                    ])
                    ->visible(fn ($record) => $record->status === 'coverage' && $record->approval_status === 'pending')
                    ->action(function ($record, array $data) {
                        $reasonEnum = CoverageReason::tryFrom($data['coverage_reason']);

                        // التحقق من سلسلة الموافقات المرتبطة بنوع الطلب
                        $approvalFlow = ApprovalFlow::where('request_type', 'coverage')
                            ->orderBy('approval_level', 'asc')
                            ->first();
                        if (! $approvalFlow) {
                            throw new \Exception(__('No approval flow defined for this request type.'));
                        }

                        // تخزين الدور الأول في `current_approver_role`
                        //   $role = Role::where('name', $approvalFlow->approver_role)->first();
                        //   if (! $role) {
                        //       throw new \Exception(__('Role not found for the approver in the approval flow.'));
                        //   }

                        // تحديث حالة الطلب إلى "موافق عليه"
                        $record->update(['approval_status' => 'submitted']);

                        // إنشاء سجل التغطية
                        $coverage = Coverage::create([
                            'employee_id' => $record->employee_id, // الموظف الأساسي
                            'absent_employee_id' => $data['absent_employee_id'] ?? null, // الموظف البديل (إذا كان مطلوبًا)
                            'zone_id' => $record->zone_id,
                            'date' => $record->date,
                            'status' => 'pending',
                            'added_by' => auth()->id(), // الحساب الحالي
                            'reason' => $data['coverage_reason'],
                            'notes' => $data['notes'],
                        ]);

                        // تحديث معرف التغطية في الحضور
                        $record->update(['coverage_id' => $coverage->id]);

                        // $data['current_approver_role'] = $role->name;

                        // **إنشاء طلب جديد تلقائيًا من نوع "التغطية"**
                        Request::create([
                            'type' => 'coverage',
                            'coverage_id' => $coverage->id,
                            'submitted_by' => auth()->id(), // المستخدم الحالي هو مقدم الطلب
                            'employee_id' => $record->employee_id, // الموظف الذي يحتاج التغطية
                            'current_approver_role' => $approvalFlow->approver_role, // الدور الحالي
                            'description' => $data['notes'],
                            'additional_data' => json_encode([
                                'coverage_reason' => $data['coverage_reason'],
                                'notes' => $data['notes'],
                            ]),
                            'status' => 'pending', // الطلب يبدأ بحالة "قيد الانتظار"
                        ]);
                    })
                    ->modalHeading(__('Approve Coverage'))
                    ->modalSubmitActionLabel(__('Approve'))
                    ->modalCancelActionLabel(__('Cancel')),

                Tables\Actions\Action::make('Reject')
                    ->label(__('Reject'))
                    ->visible(fn ($record) => $record->status === 'coverage' && $record->approval_status === 'pending') // الشرط لإظهار الزر
                    ->action(fn ($record) => $record->update(['approval_status' => 'rejected'])),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
