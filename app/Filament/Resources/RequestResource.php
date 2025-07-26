<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Request;
use App\Models\Employee;
use App\Models\Attachment;
use Filament\Resources\Resource;
use App\Models\EmployeeProjectRecord;
use App\Tables\Filters\EmployeeFilter;
use App\Forms\Components\EmployeeSelect;
use Filament\Notifications\Notification;

use Illuminate\Database\Eloquent\Builder;
use App\Forms\Components\EmployeeSelectV2;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\RequestResource\Pages;

use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\RequestResource\RelationManagers;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    protected static ?string $navigationIcon = 'fas-file-pen';

    public $selectedEmployeeId;

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
        return __('Requests');
    }

    public static function getPluralLabel(): string
    {
        return __('Requests');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Request Management');
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([

            Forms\Components\Tabs::make('Tabs')
                ->tabs([

                    Forms\Components\Tabs\Tab::make('Request')
                        ->label(__('Request'))
                        ->schema([
                            // اختيار نوع الطلب
                            Forms\Components\Select::make('type')
                                ->label(__('Type'))
                                ->options(\App\Models\RequestType::where('is_active', true) // ✅ تصفية الأنواع القابلة للاستخدام فقط
                                    ->get()
                                    ->mapWithKeys(fn($type) => [$type->key => __($type->name)]) // ✅ ترجمة ديناميكية
                                    ->toArray())
                                ->required()
                                ->reactive()
                                ->disabledOn('edit'),

                            // اختيار الموظف
                            // Forms\Components\Select::make('employee_id')
                            //     ->label(__('Employee'))
                            //     ->options(Employee::all()->pluck('first_name', 'id'))
                            //     ->searchable()
                            //     ->nullable()
                            //     ->required(),
                            EmployeeSelect::make()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                    $set('employee_project_record_id', null);

                                    $today = Carbon::today()->toDateString();
                                    $records = EmployeeProjectRecord::where('employee_id', $state)
                                        ->where(function ($query) use ($today) {
                                            $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                                        })
                                        ->pluck('id');

                                    if ($records->count() === 1) {
                                        // ✅ إذا وُجد إسناد واحد فقط يتم اختياره مباشرة
                                        $set('employee_project_record_id', $records->first());
                                    }

                                    // إذا أردت فرض إعادة تحميل الحقول الديناميكية يمكنك تحديث أي متغير آخر
                                    // $livewire->dispatch('refreshAssignmentOptions');
                                }),


                            // المقدم
                            Forms\Components\Select::make('submitted_by')
                                ->label(__('Submitted By'))
                                ->options(User::all()->pluck('name', 'id'))
                                ->default(auth()->id())
                                ->disabled()
                                ->searchable()
                                ->required(),

                            // وصف الطلب
                            Forms\Components\Textarea::make('description')
                                ->label(__('Description')),

                        ])
                        ->columns(2),

                    // الحقول الديناميكية بناءً على نوع الطلب
                    // إذا كان نوع الطلب "إجازة"
                    Forms\Components\Tabs\Tab::make('Leave Details')
                        ->label(__('Leave Details'))
                        ->schema([
                            // تاريخ البداية
                            Forms\Components\DatePicker::make('start_date')
                                ->label(__('Start Date'))
                                ->required()
                                ->reactive()
                                ->visible(fn($get) => $get('type') === 'leave'),

                            // تاريخ النهاية
                            Forms\Components\DatePicker::make('end_date')
                                ->label(__('End Date'))
                                ->required()
                                ->reactive()
                                ->visible(fn($get) => $get('type') === 'leave'),

                            // المدة
                            Forms\Components\TextInput::make('duration')
                                ->label(__('Duration (Days)'))
                                ->numeric()
                                ->disabled(false)
                                ->default(fn($get) => $get('start_date') && $get('end_date')
                                    ? \Carbon\Carbon::parse($get('start_date'))->diffInDays(\Carbon\Carbon::parse($get('end_date'))) + 1
                                    : null)
                                ->visible(fn($get) => $get('type') === 'leave'),

                            // نوع الإجازة
                            Forms\Components\Select::make('leave.leave_type_id')
                                ->label('نوع الإجازة')
                                ->relationship('leave.leaveType', 'name')
                                ->searchable()
                                ->required()
                                ->visible(fn($get) => $get('type') === 'leave')
                                ->preload(),
                            Forms\Components\Select::make('leave.employee_project_record_id')
                                ->label('الإسناد (الموقع والوردية)')
                                ->reactive()
                                ->required()
                                ->visible(fn($get) => $get('type') === 'leave')
                                ->options(function ($livewire) {
                                    $employeeId = data_get($livewire->data, 'employee_id');

                                    if (! $employeeId) return [];

                                    $today = now()->toDateString();

                                    return \App\Models\EmployeeProjectRecord::with(['project', 'zone', 'shift'])
                                        ->where('employee_id', $employeeId)
                                        ->where(function ($q) use ($today) {
                                            $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                                        })
                                        ->get()
                                        ->mapWithKeys(fn($record) => [
                                            $record->id => $record->project->name . ' - ' .
                                                $record->zone->name . ' - ' .
                                                $record->shift->name
                                        ])
                                        ->toArray();
                                })
                                ->searchable(),


                            // السبب
                            Forms\Components\Textarea::make('reason')
                                ->label(__('Reason'))
                                ->nullable()
                                ->visible(fn($get) => $get('type') === 'leave'),

                            Forms\Components\Repeater::make('leave_substitutes') // أي اسم مؤقت غير مرتبط مباشرة
                                ->label('الموظفون البدلاء أثناء الإجازة')
                                ->schema([
                                    // Forms\Components\Select::make('substitute_employee_id')
                                    //     ->label('الموظف البديل')
                                    //     ->searchable()
                                    //     ->options(\App\Models\Employee::pluck('full_name', 'id')),
                                    EmployeeSelectV2::make('substitute_employee_id')
                                        ->label('الموظف البديل')
                                        ->preload(),

                                    Forms\Components\DatePicker::make('start_date')
                                        ->label('من تاريخ')
                                        ->required(),

                                    Forms\Components\DatePicker::make('end_date')
                                        ->label('إلى تاريخ')
                                        ->required(),
                                ])
                                ->minItems(0)
                                ->columnSpanFull()
                                ->afterStateHydrated(function ($component, $state) {
                                    // تحميل البدلاء من العلاقة اليدويًا
                                    if (request()->routeIs('filament.admin.resources.requests.edit')) {
                                        $leave = $component->getContainer()->getLivewire()->record->leave;
                                        if ($leave) {
                                            $component->state(
                                                $leave->substitutes->map(fn($sub) => [
                                                    'substitute_employee_id' => $sub->substitute_employee_id,
                                                    'start_date' => $sub->start_date,
                                                    'end_date' => $sub->end_date,
                                                ])->toArray()
                                            );
                                        }
                                    }
                                })
                                ->columns(3)
                                ->afterStateUpdated(function ($state, $livewire) {
                                    $livewire->data['leave_substitutes'] = $state;
                                }),
                        ])->columns(2)
                        ->visible(fn($get) => $get('type') === 'leave'),

                    Forms\Components\Tabs\Tab::make('Exclusion Details')
                        ->label(__('Exclusion Details'))
                        ->schema([
                            Forms\Components\Select::make('exclusion_type')
                                ->label(__('Exclusion Type'))
                                ->options(
                                    collect(\App\Enums\ExclusionType::cases())
                                        ->mapWithKeys(fn($type) => [$type->value => $type->label()])
                                        ->toArray()
                                )
                                ->required(),

                            Forms\Components\DatePicker::make('exclusion_date')
                                ->label(__('Exclusion Date'))
                                ->required(),




                            Forms\Components\Select::make('employee_project_record_id')
                                ->label(__('Assignment'))
                                ->reactive()
                                ->afterStateHydrated(function (callable $set, callable $get, $state) {
                                    $employeeId = $get('employee_id');

                                    if (! $employeeId) return;

                                    $today = Carbon::today()->toDateString();

                                    $records = EmployeeProjectRecord::where('employee_id', $employeeId)
                                        ->where(function ($query) use ($today) {
                                            $query->whereNull('end_date')
                                                ->orWhereDate('end_date', '>=', $today);
                                        })
                                        ->pluck('id');

                                    if ($records->count() === 1) {
                                        $set('exclusion.employee_project_record_id', $records->first());
                                    }
                                })
                                ->options(function ($livewire) {
                                    $employeeId = data_get($livewire->data, 'employee_id');

                                    if (! $employeeId) return [];

                                    $today = Carbon::today()->toDateString();

                                    $records = EmployeeProjectRecord::with(['project', 'zone', 'shift'])
                                        ->where('employee_id', $employeeId)
                                        ->where(function ($query) use ($today) {
                                            $query->whereNull('end_date')
                                                ->orWhereDate('end_date', '>=', $today);
                                        })
                                        ->get();

                                    return $records->mapWithKeys(fn($record) => [
                                        $record->id => $record->project->name . ' - ' .
                                            $record->zone->name . ' - ' .
                                            $record->shift->name
                                    ])->toArray();
                                })
                                // ->required(fn($get) => $get('type') === 'exclusion')
                                ->visible(fn($get) => $get('type') === 'exclusion')
                                ->columnSpanFull()
                                ->searchable(),


                            // Forms\Components\Textarea::make('exclusion_reason')
                            //     ->label(__('Reason'))
                            //     ->nullable(),

                            // Forms\Components\FileUpload::make('exclusion_attachment')
                            //     ->label(__('Attachment'))
                            //     ->nullable(),
                            // Forms\Components\Repeater::make('attachments')
                            // ->label(__('Attachments'))
                            // ->relationship('attachments') // استخدام العلاقة المباشرة من موديل Exclusion
                            // ->schema([
                            //     Forms\Components\FileUpload::make('file_url')
                            //         ->label(__('File'))
                            //         ->directory('exclusions/attachments') // تحديد مسار المرفقات
                            //         ->required(),
                            // ])
                            // ->columns(1)
                            // ->minItems(1) // الحد الأدنى للمرفقات
                            // ->maxItems(5), // الحد الأقصى للمرفقات

                            // Forms\Components\Textarea::make('exclusion_notes')
                            //     ->label(__('Notes'))
                            //     ->nullable(),
                        ])
                        ->columns(2)
                        ->visible(fn($get) => $get('type') === 'exclusion'), // يظهر فقط إذا كان نوع الطلب استبعاد

                    Forms\Components\Tabs\Tab::make('Loan Details')
                        ->label(__('Loan Details'))
                        ->schema([
                            // حقول طلبات أخرى مثل القروض
                            Forms\Components\TextInput::make('amount')
                                ->label(__('Amount'))
                                ->visible(fn($livewire, $get) => $get('type') === 'loan')
                                ->numeric(),
                        ])
                        ->columns(2)
                        ->visible(fn($livewire, $get) => $get('type') === 'loan'),

                    Forms\Components\Tabs\Tab::make(__('Attachments'))
                        ->schema([
                            Forms\Components\Repeater::make('attachments')
                                ->label(__('Attachments'))
                                ->relationship('attachments') // الربط بالعلاقة في موديل Request
                                ->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->label(__('Title'))
                                        ->required(),

                                    Forms\Components\SpatieMediaLibraryFileUpload::make('file')
                                        ->label(__('Upload File'))
                                        ->collection('attachments')
                                        ->multiple()
                                        ->disk('s3') // ✅ رفع الملفات مباشرة إلى S3
                                        // ->preserveFilenames()
                                        ->maxFiles(5)
                                        ->maxSize(10240),

                                    // Forms\Components\DatePicker::make('expiry_date')
                                    //     ->label(__('Expiry Date'))
                                    //     ->nullable(),

                                    // Forms\Components\Textarea::make('notes')
                                    //     ->label(__('Notes'))
                                    //     ->nullable(),

                                    // ✅ **إضافة حقل مخفي لتمرير employee_id تلقائيًا**
                                    // Forms\Components\Select::make('employee_id')
                                    // ->label(__('Employee'))
                                    // ->searchable()
                                    // ->placeholder(__('Search for an employee...'))
                                    // ->getSearchResultsUsing(function (string $search) {
                                    //     return \App\Models\Employee::query()
                                    //         ->where('national_id', 'like', "%{$search}%") // البحث باستخدام رقم الهوية
                                    //         ->orWhere('first_name', 'like', "%{$search}%") // البحث باستخدام الاسم الأول
                                    //         ->orWhere('family_name', 'like', "%{$search}%") // البحث باستخدام اسم العائلة
                                    //         ->limit(50)
                                    //         ->get()
                                    //         ->mapWithKeys(function ($employee) {
                                    //             return [
                                    //                 $employee->id => "{$employee->first_name} {$employee->family_name} ({$employee->id})",
                                    //             ]; // عرض الاسم الأول، العائلة، والمعرف
                                    //         });
                                    // })
                                    // ->getOptionLabelUsing(function ($value) {
                                    //     $employee = \App\Models\Employee::find($value);

                                    //     return $employee
                                    //         ? "{$employee->first_name} {$employee->family_name} ({$employee->id})" // عرض الاسم والمعرف عند الاختيار
                                    //         : null;
                                    // })

                                    // ->preload()

                                    // ->required(), // ✅ إخفاؤه فقط إذا لم يتم اختيار موظف
                                    // تمرير `employee_id` إلى كل مرفق
                                ])
                                ->columns(2)
                                ->minItems(0)
                                ->maxItems(10),
                        ])
                        ->columns(1),
                    // ->visible(fn ($get) => !empty($get('employee_id'))),

                ])
                // ->columns(1)
                ->persistTabInQueryString(),

        ])->columns(1);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->formatStateUsing(fn($state) => __($state)),
                Tables\Columns\TextColumn::make('submittedBy.name')
                    ->label(__('Submitted By'))
                    ->searchable(), // تمكين البحث
                Tables\Columns\TextColumn::make('full_name')
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
                    }),

                Tables\Columns\TextColumn::make('employee.national_id')
                    ->label(__('National ID'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn($state) => __($state))
                    ->color(fn($state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('current_approver_role')
                    ->label(__('Current Approver Role'))
                    ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('duration')
                    ->label(__('Duration (Days)'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('Description'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('exclusion_date')
                    ->label(__('Exclusion Date'))
                    ->getStateUsing(fn($record) => $record->exclusion?->exclusion_date)
                    ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('Y-m-d') : '-')
                    ->sortable(),


                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('attachments_count')
                // ->label(__('Attachments Count'))
                // ->getStateUsing(fn ($record) => $record->exclusion ? $record->exclusion->attachments->count() : 0)
                // ->sortable()
                // ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('additional_data')
                    ->label(__('Additional Data'))
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '-'; // إذا لم يكن هناك بيانات إضافية
                        }

                        // التحقق مما إذا كانت البيانات JSON أم نص عادي
                        $decoded = json_decode($state, true);

                        if (json_last_error() === JSON_ERROR_NONE) {
                            // ✅ إذا كانت البيانات JSON، قم بعرضها بتنسيق مناسب
                            return collect($decoded)
                                ->map(fn($value, $key) => ucfirst(str_replace('_', ' ', $key)) . ': ' . (is_array($value) ? json_encode($value) : $value))
                                ->join(' | ');
                        }

                        // ✅ إذا لم تكن JSON، اعرضها كنص عادي
                        return $state;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approvalFlows')
                    ->label(__('Remaining Levels'))
                    ->formatStateUsing(function ($record) {
                        // جلب جميع المستويات المرتبطة بالطلب
                        $approvalFlows = $record->approvalFlows;

                        // جلب أعلى مستوى تم الموافقة عليه
                        $approvedLevels = $record->approvals
                            ->where('status', 'approved')
                            ->pluck('approval_level')
                            ->toArray();

                        // تحديد المستويات المتبقية بناءً على `approval_level`
                        $remainingFlows = $approvalFlows
                            ->filter(fn($flow) => ! in_array($flow->approval_level, $approvedLevels))
                            ->map(fn($flow) => __(':role (Level :level)', [
                                'role' => $flow->approver_role,
                                'level' => $flow->approval_level,
                            ]));

                        return $remainingFlows->isEmpty() ? __('No remaining approvals') : $remainingFlows->join(', ');
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([

                // طلباتي
                Tables\Filters\Filter::make('my_requests')
                    ->label(__('My Requests'))
                    ->query(function (Builder $query) {
                        return $query->where('submitted_by', auth()->id());
                    })
                    ->toggle(),

                // حسب الموظف
                // EmployeeFilter::make('employee_id'), // إضافة فلتر الموظفين
                EmployeeFilter::make('employee_filter'),
                // حسب النوع
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options(\App\Models\RequestType::pluck('name', 'key')->map(fn($name) => __($name))),

                // حسب الحالة
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'pending' => __('Pending'),
                        'approved' => __('Approved'),
                        'rejected' => __('Rejected'),
                    ]),

                // حسب تاريخ الإنشاء
                // Tables\Filters\DateFilter::make('created_at')
                //     ->label(__('Created At')),
                // Tables\Filters\SelectFilter::make('type')
                //     ->label(__('Type'))
                //     ->options([
                //         'leave' => __('Leave Request'),
                //         'transfer' => __('Transfer Request'),
                //         'compensation' => __('Compensation Request'),
                //     ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Type'))

                    ->options(\App\Models\RequestType::all()->pluck('name', 'key')->map(fn($name) => __($name)) // ترجمة الأسماء (في حال كانت لديك مفاتيح ترجمة)
                        ->toArray()),

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'pending' => __('Pending'),
                        'approved' => __('Approved'),
                        'rejected' => __('Rejected'),
                    ]),
                Tables\Filters\SelectFilter::make('current_approver_role')
                    ->label(__('Current Approver Role'))
                    ->options([
                        'hr' => __('HR'),
                        'manager' => __('Manager'),
                        'general_manager' => __('General Manager'),
                    ]),
            ])
            ->actions([
                // Tables\Actions\Action::make('do')
                // ->label(__('اعتماد الإجازة'))
                // ->icon('heroicon-o-check')
                // ->color('success')
                // ->action(function ($record) {
                //     // تأكد من أن الطلب هو من نوع إجازة وأن هناك سجل إجازة مرتبط به
                //     if ($record->type === 'leave' && $record->leave) {
                //         $record->leave->update([
                //             'approved' => true, // تحديث حالة الإجازة إلى "معتمدة"
                //         ]);

                //         // يمكنك إرسال تنبيه (Notification) للمستخدم
                //         Notification::make()
                //             ->title('تم اعتماد الإجازة بنجاح')
                //             ->success()
                //             ->send();
                //     } else {
                //         // إذا لم يكن هذا الطلب من نوع إجازة أو لا توجد إجازة مرتبطة
                //         Notification::make()
                //             ->title('لا توجد إجازة مرتبطة بهذا الطلب')
                //             ->danger()
                //             ->send();
                //     }
                // }),
                // ✅ **زر "إرفاق ملف"**
                Tables\Actions\Action::make('approve')
                    ->label(__('Approve'))
                    ->action(fn($record, array $data) => $record->approveRequest(auth()->user(), $data['comments']))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('comments')
                            ->label(__('Comments'))
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->hidden(function ($record) {
                        // إخفاء الزر إذا لم يكن الطلب "pending"
                        if ($record->status !== 'pending') {
                            return true;
                        }

                        // إخفاء الزر إذا كان الطلب استبعاد موعده في المستقبل
                        if (
                            $record->type === 'exclusion' &&
                            $record->exclusion &&
                            \Carbon\Carbon::parse($record->exclusion->exclusion_date)->isFuture()
                        ) {
                            return true;
                        }

                        return false; // غير مخفي في باقي الحالات
                    }),
                Tables\Actions\Action::make('reject')
                    ->label(__('Reject'))
                    ->action(fn($record, array $data) => $record->rejectRequest(auth()->user(), $data['comments']))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('comments')
                            ->label(__('Reason for Rejection'))
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->hidden(fn($record) => $record->status !== 'pending'),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('attach_file')
                    ->label(__('Attach File'))
                    ->icon('heroicon-o-paper-clip')
                    ->color('primary')
                    ->form([
                        Forms\Components\TextInput::make('title')
                            ->label(__('File Title'))
                            ->required(),

                        Forms\Components\FileUpload::make('file')
                            ->label(__('Upload File'))
                            // ->collection('attachments')
                            ->disk('s3') // ✅ حفظ إلى S3
                            // ->preserveFilenames()
                            ->storeFiles()
                            ->directory('form-attachments')
                            ->visibility('private')
                            ->required(),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->label(__('Expiry Date'))
                            ->nullable(),

                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes'))
                            ->nullable(),
                    ])
                    ->action(function (array $data, Request $record) {

                        // ✅ إنشاء مرفق جديد مربوط بالطلب
                        $attachment = new Attachment([
                            'title' => $data['title'],
                            'expiry_date' => $data['expiry_date'],
                            'notes' => $data['notes'],
                            'added_by' => auth()->id(),
                            'model_type' => Request::class,
                            'model_id' => $record->id,
                        ]);

                        $attachment->save();

                        // ✅ **إرفاق الملف باستخدام `addMediaFromDisk()`**
                        if (! empty($data['file']) && is_string($data['file'])) {
                            \Log::info('File path received:', ['file' => $data['file']]);

                            $attachment->addMediaFromDisk($data['file'], 's3') // ✅ استخدم `addMediaFromDisk()`
                                ->toMediaCollection('attachments', 's3'); // ✅ حفظ في S3
                        } else {
                            \Log::error('No valid file path detected.');
                        }

                        Notification::make()
                            ->title(__('File Attached'))
                            ->body(__('The file has been successfully attached to the request.'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->hidden(fn($record) => in_array($record->status, ['approved', 'rejected'])),
            ])
            ->paginationPageOptions([10, 25, 50, 100])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                // ExportBulkAction::make()
                //     ->label(__('Export to Excel'))
                //     ->exporter(ExcelExport::make()
                //         ->fromTable() // تصدير البيانات كما هي من الجدول
                //         ->withColumns([ // تحديد الأعمدة المراد تصديرها
                //             'id' => __('Request ID'),
                //             'type' => __('Type'),
                //             'submittedBy.name' => __('Submitted By'),
                //             'employee.first_name' => __('Employee'),
                //             'status' => __('Status'),
                //             'current_approver_role' => __('Current Approver Role'),
                //             'duration' => __('Duration (Days)'),
                //             'amount' => __('Amount'),
                //             'additional_data' => __('Additional Data'),
                //             'created_at' => __('Created At'),
                //         ])
                //     ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'edit' => Pages\EditRequest::route('/{record}/edit'),
            'view' => Pages\ViewRequest::route('/{record}/view'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ApprovalsRelationManager::class,
        ];
    }
}
