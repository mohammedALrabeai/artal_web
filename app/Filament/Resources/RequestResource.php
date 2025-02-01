<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Policy;
use App\Models\Request;
use App\Models\Employee;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\RequestResource\Pages;
use App\Filament\Resources\RequestResource\RelationManagers;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    public $selectedEmployeeId;

    public static function getNavigationBadge(): ?string
    {
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
                                ->options(\App\Models\RequestType::all()
                                    ->mapWithKeys(fn ($type) => [$type->key => __($type->name)]) // ترجمة ديناميكية
                                    ->toArray())
                                ->required()
                                ->reactive(),

                            // اختيار الموظف
                            // Forms\Components\Select::make('employee_id')
                            //     ->label(__('Employee'))
                            //     ->options(Employee::all()->pluck('first_name', 'id'))
                            //     ->searchable()
                            //     ->nullable()
                            //     ->required(),
                            Forms\Components\Select::make('employee_id')
                                ->label(__('Employee'))
                                ->searchable()
                                ->placeholder(__('Search for an employee...'))
                                ->getSearchResultsUsing(function (string $search) {
                                    return \App\Models\Employee::query()
                                        ->where('national_id', 'like', "%{$search}%") // البحث باستخدام رقم الهوية
                                        ->orWhere('first_name', 'like', "%{$search}%") // البحث باستخدام الاسم الأول
                                        ->orWhere('family_name', 'like', "%{$search}%") // البحث باستخدام اسم العائلة
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(function ($employee) {
                                            return [
                                                $employee->id => "{$employee->first_name} {$employee->family_name} ({$employee->id})",
                                            ]; // عرض الاسم الأول، العائلة، والمعرف
                                        });
                                })
                                ->getOptionLabelUsing(function ($value) {
                                    $employee = \App\Models\Employee::find($value);

                                    return $employee
                                        ? "{$employee->first_name} {$employee->family_name} ({$employee->id})" // عرض الاسم والمعرف عند الاختيار
                                        : null;
                                })
                                ->reactive() // ✅ يجعل الحقل ديناميكيًا
                                ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                    \Log::info('Employee selected:', ['employee_id' => $state]); // ✅ التحقق من القيمة في الـ Log
                                    $livewire->selectedEmployeeId = $state; // ✅ تحديث `selectedEmployeeId` في Livewire
                                    $set('selectedEmployeeId', $state); // ✅ تحديث داخل النموذج
                                })
                                ->preload()

                                ->required(),

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
                                ->visible(fn ($get) => $get('type') === 'leave'),

                            // تاريخ النهاية
                            Forms\Components\DatePicker::make('end_date')
                                ->label(__('End Date'))
                                ->required()
                                ->reactive()
                                ->visible(fn ($get) => $get('type') === 'leave'),

                            // المدة
                            Forms\Components\TextInput::make('duration')
                                ->label(__('Duration (Days)'))
                                ->numeric()
                                ->disabled(false)
                                ->default(fn ($get) => $get('start_date') && $get('end_date')
                                    ? \Carbon\Carbon::parse($get('start_date'))->diffInDays(\Carbon\Carbon::parse($get('end_date'))) + 1
                                    : null)
                                ->visible(fn ($get) => $get('type') === 'leave'),

                            // نوع الإجازة
                            Forms\Components\Select::make('leave_type')
                                ->label(__('Leave Type'))
                                ->options([
                                    'annual' => __('Annual Leave'),
                                    'sick' => __('Sick Leave'),
                                    'unpaid' => __('Unpaid Leave'),
                                ])
                                ->required()
                                ->visible(fn ($get) => $get('type') === 'leave'),

                            // السبب
                            Forms\Components\Textarea::make('reason')
                                ->label(__('Reason'))
                                ->nullable()
                                ->visible(fn ($get) => $get('type') === 'leave'),
                        ])->columns(2)
                        ->visible(fn ($get) => $get('type') === 'leave'),

                    Forms\Components\Tabs\Tab::make('Exclusion Details')
                        ->label(__('Exclusion Details'))
                        ->schema([
                            Forms\Components\Select::make('exclusion_type')
                                ->label(__('Exclusion Type'))
                                ->options(
                                    collect(\App\Enums\ExclusionType::cases())
                                        ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
                                        ->toArray()
                                )
                                ->required(),

                            Forms\Components\DatePicker::make('exclusion_date')
                                ->label(__('Exclusion Date'))
                                ->required(),

                            Forms\Components\Textarea::make('exclusion_reason')
                                ->label(__('Reason'))
                                ->nullable(),

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
                        
                        
                        

                            Forms\Components\Textarea::make('exclusion_notes')
                                ->label(__('Notes'))
                                ->nullable(),
                        ])
                        ->columns(2)
                        ->visible(fn ($get) => $get('type') === 'exclusion'), // يظهر فقط إذا كان نوع الطلب استبعاد

                    Forms\Components\Tabs\Tab::make('Loan Details')
                        ->label(__('Loan Details'))
                        ->schema([
                            // حقول طلبات أخرى مثل القروض
                            Forms\Components\TextInput::make('amount')
                                ->label(__('Amount'))
                                ->visible(fn ($livewire, $get) => $get('type') === 'loan')
                                ->numeric(),
                        ])
                        ->columns(2)
                        ->visible(fn ($livewire, $get) => $get('type') === 'loan'),
                      
                        Forms\Components\Tabs\Tab::make(__('Attachments'))
            ->schema([
                Forms\Components\Repeater::make('attachments')
                    ->label(__('Attachments'))
                    ->relationship('attachments') // الربط بالعلاقة في موديل Request
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('Title'))
                            ->required(),
                        
                        Forms\Components\Select::make('type')
                            ->label(__('Type'))
                            ->options([
                                'text' => __('Text'),
                                'link' => __('Link'),
                                'image' => __('Image'),
                                'video' => __('Video'),
                                'file' => __('File'),
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\Textarea::make('content')
                            ->label(__('Content (Text)'))
                            ->nullable()
                            ->visible(fn ($get) => $get('type') === 'text'),

                        Forms\Components\TextInput::make('content')
                            ->label(__('Content (Link)'))
                            ->nullable()
                            ->visible(fn ($get) => $get('type') === 'link')
                            ->url(),
                            // Forms\Components\TextInput::make('image_url')
                        Forms\Components\FileUpload::make('image_url')
                            ->label(__('Content (Image)'))
                            // ->nullable()
                            ->disk('s3')
                            ->directory('attachments/images')
                            ->visibility('public')
                            ->visible(fn ($get) => $get('type') === 'image'),

                        Forms\Components\FileUpload::make('video_url')
                            ->label(__('Content (Video)'))
                            ->nullable()
                            ->disk('s3')
                            ->directory('attachments/videos')
                            ->visibility('public')
                            ->acceptedFileTypes(['video/*'])
                            ->visible(fn ($get) => $get('type') === 'video'),

                        Forms\Components\FileUpload::make('file_url')
                            ->label(__('Content (File)'))
                            ->nullable()
                            ->disk('s3')
                            ->directory('attachments/files')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/*'])
                            ->visible(fn ($get) => $get('type') === 'file'),

                        Forms\Components\DatePicker::make('expiry_date')
                            ->label(__('Expiry Date'))
                            ->nullable(),

                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes'))
                            ->nullable(),

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
            ->columns(1)
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
                    ->formatStateUsing(fn ($state) => __($state)),
                Tables\Columns\TextColumn::make('submittedBy.name')
                    ->label(__('Submitted By'))
                    ->searchable(), // تمكين البحث
                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label(__('Employee'))
                    ->formatStateUsing(fn ($state, $record) => "{$record->employee->first_name} {$record->employee->family_name}")
                    ->searchable(), // تمكين البحث
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn ($state) => __($state))
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => null,
                    }),
                 
                Tables\Columns\TextColumn::make('current_approver_role')
                    ->label(__('Current Approver Role'))
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('duration')
                    ->label(__('Duration (Days)'))
                    ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('description')
                    ->label(__('Description'))
                    ->searchable(),
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
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state) : '-')
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
                            ->filter(fn ($flow) => ! in_array($flow->approval_level, $approvedLevels))
                            ->map(fn ($flow) => __(':role (Level :level)', [
                                'role' => $flow->approver_role,
                                'level' => $flow->approval_level,
                            ]));

                        return $remainingFlows->isEmpty() ? __('No remaining approvals') : $remainingFlows->join(', ');
                    })
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
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label(__('Employee'))
                    ->options(Employee::all()->pluck('first_name', 'id'))
                    ->searchable(),

                // حسب النوع
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options(\App\Models\RequestType::pluck('name', 'key')->map(fn ($name) => __($name))),

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

                    ->options(\App\Models\RequestType::all()->pluck('name', 'key')->map(fn ($name) => __($name)) // ترجمة الأسماء (في حال كانت لديك مفاتيح ترجمة)
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
                Tables\Actions\Action::make('approve')
                    ->label(__('Approve'))
                    ->action(fn ($record, array $data) => $record->approveRequest(auth()->user(), $data['comments']))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('comments')
                            ->label(__('Comments'))
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status !== 'pending'),
                Tables\Actions\Action::make('reject')
                    ->label(__('Reject'))
                    ->action(fn ($record, array $data) => $record->rejectRequest(auth()->user(), $data['comments']))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('comments')
                            ->label(__('Reason for Rejection'))
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status !== 'pending'),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'edit' => Pages\EditRequest::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ApprovalsRelationManager::class,
        ];
    }
}
