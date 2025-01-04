<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = -1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return __('Employees');
    }

    public static function getPluralLabel(): string
    {
        return __('Employees');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Employee Management');
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\EmployeeResource\Widgets\ExportEmployeesWidget::class,
        ];
    }

    public static function getHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('export')
                ->label(__('Export All'))
                ->icon('heroicon-o-arrow-down-tray') // استخدم أيقونة متوفرة
                ->color('primary') // يمكن تغيير اللون إذا لزم الأمر
                ->action(function () {
                    return \Pxlrbt\FilamentExcel\Exports\ExcelExport::make()
                        ->table('employees') // اسم الجدول
                        ->columns([
                            'first_name' => __('First Name'),
                            'family_name' => __('Family Name'),
                            'national_id' => __('National ID'),
                            'job_status' => __('Job Status'),
                            'email' => __('Email'),
                        ])
                        ->filename('all_employees.pdf')
                        ->pdf(); // تصدير بصيغة PDF
                }),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Personal Information
            Forms\Components\Fieldset::make(__('Personal Information'))
                ->schema([
                    Forms\Components\TextInput::make('first_name')
                        ->label(__('First Name'))
                        ->required(),

                    Forms\Components\TextInput::make('father_name')
                        ->label(__('Father Name'))
                        ->required(),

                    Forms\Components\TextInput::make('grandfather_name')
                        ->label(__('Grandfather Name'))
                        ->required(),

                    Forms\Components\TextInput::make('family_name')
                        ->label(__('Family Name'))
                        ->required(),

                    Forms\Components\DatePicker::make('birth_date')
                        ->label(__('Birth Date'))
                        ->required(),

                    Forms\Components\TextInput::make('national_id')
                        ->label(__('National ID'))
                        ->required(),

                    Forms\Components\DatePicker::make('national_id_expiry')
                        ->label(__('National ID Expiry'))
                        ->required(),

                    Forms\Components\TextInput::make('nationality')
                        ->label(__('Nationality'))
                        ->required(),

                    Forms\Components\TextInput::make('bank_account')
                        ->label(__('Bank Account'))
                        ->required(),

                    Forms\Components\TextInput::make('sponsor_company')
                        ->label(__('Sponsor Company'))
                        ->required(),

                    Forms\Components\Select::make('insurance_company_id')
                        ->label(__('Insurance Company'))
                        ->relationship('insuranceCompany', 'name') // ربط العلاقة مع جدول شركات التأمين
                        ->options(function () {
                            return \App\Models\InsuranceCompany::pluck('name', 'id')->prepend('لا توجد شركة تأمين', '');
                        }) // إضافة خيار لتصفير القيمة
                        ->placeholder('اختر شركة التأمين') // النص الافتراضي
                        ->nullable() // السماح للحقل بأن يكون فارغًا
                        ->searchable() // دعم البحث
                        ->preload(), // تحميل البيانات مسبقًا

                    Forms\Components\TextInput::make('blood_type')
                        ->label(__('Blood Type'))
                        ->required(),
                ]),

            // Job Information
            Forms\Components\Fieldset::make(__('Job Information'))
                ->schema([
                    Forms\Components\DatePicker::make('contract_start')
                        ->label(__('Contract Start'))
                        ->required(),
                    Forms\Components\DatePicker::make('contract_end')
                        ->label(__('Contract End'))

                        ->minDate(now()) // لضمان اختيار تاريخ مستقبلي
                        ->displayFormat('Y-m-d')
                        ->placeholder(__('Select contract end date')),

                    Forms\Components\DatePicker::make('actual_start')
                        ->label(__('Actual Start'))
                        ->required(),

                    Forms\Components\TextInput::make('basic_salary')
                        ->label(__('Basic Salary'))
                        ->numeric()
                        ->required(),

                    Forms\Components\TextInput::make('living_allowance')
                        ->label(__('Living Allowance'))
                        ->numeric(),

                    Forms\Components\TextInput::make('other_allowances')
                        ->label(__('Other Allowances'))
                        ->numeric(),

                    Forms\Components\TextInput::make('job_status')
                        ->label(__('Job Status'))
                        ->required(),

                    Forms\Components\TextInput::make('health_insurance_status')
                        ->label(__('Health Insurance Status'))
                        ->required(),

                    Forms\Components\TextInput::make('health_insurance_company')
                        ->label(__('Health Insurance Company')),

                    Forms\Components\TextInput::make('social_security')
                        ->label(__('Social Security')),

                    Forms\Components\TextInput::make('social_security_code')
                        ->label(__('Social Security Code')),
                ]),

            // Education
            Forms\Components\Fieldset::make(__('Education'))
                ->schema([
                    Forms\Components\TextInput::make('qualification')
                        ->label(__('Qualification'))
                        ->required(),

                    Forms\Components\TextInput::make('specialization')
                        ->label(__('Specialization'))
                        ->required(),
                ]),

            // Contact Information
            Forms\Components\Fieldset::make(__('Contact Information'))
                ->schema([
                    Forms\Components\TextInput::make('mobile_number')
                        ->label(__('Mobile Number'))
                        ->required(),

                    Forms\Components\TextInput::make('phone_number')
                        ->label(__('Phone Number')),

                    Forms\Components\TextInput::make('email')
                        ->label(__('Email'))
                        ->email(),
                ]),

            // Address
            Forms\Components\Fieldset::make(__('Address'))
                ->schema([
                    Forms\Components\TextInput::make('region')
                        ->label(__('Region'))
                        ->required(),

                    Forms\Components\TextInput::make('city')
                        ->label(__('City'))
                        ->required(),

                    Forms\Components\TextInput::make('street')
                        ->label(__('Street'))
                        ->required(),

                    Forms\Components\TextInput::make('building_number')
                        ->label(__('Building Number'))
                        ->required(),

                    Forms\Components\TextInput::make('apartment_number')
                        ->label(__('Apartment Number')),

                    Forms\Components\TextInput::make('postal_code')
                        ->label(__('Postal Code')),
                ]),

            // Social Media
            Forms\Components\Fieldset::make(__('Social Media'))
                ->schema([
                    Forms\Components\TextInput::make('facebook')
                        ->label(__('Facebook')),

                    Forms\Components\TextInput::make('twitter')
                        ->label(__('Twitter')),

                    Forms\Components\TextInput::make('linkedin')
                        ->label(__('LinkedIn')),
                ]),
            Repeater::make('leaveBalances')
                ->relationship('leaveBalances')
                ->schema([
                    Select::make('leave_type')
                        ->label('نوع الإجازة')
                        ->options([
                            'annual' => 'سنوية',
                            'sick' => 'مرضية',
                            'other' => 'أخرى',
                        ])
                        ->required(),
                    TextInput::make('annual_leave_days')
                        ->label('عدد الأيام السنوية')
                        ->numeric()
                        ->required(), // الحقل الجديد
                    TextInput::make('balance')
                        ->label('الرصيد المتبقي')
                        ->numeric()
                        ->disabled(), // لا يمكن تحريره يدويًا
                    TextInput::make('accrued_per_month')
                        ->label('المستحق شهريًا')
                        ->numeric(),
                    TextInput::make('used_balance')
                        ->label('المستخدم')
                        ->numeric(),
                ])
                ->label('رصيد الإجازات')
                ->columns(4), // تحديث عدد الأعمدة ليشمل الحقل الجديد

            // Security
            Forms\Components\Fieldset::make(__('Security'))
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label(__('Password'))
                        ->password()
                        ->required(),

                    Forms\Components\Select::make('added_by')
                        ->label(__('Added By'))
                        ->options(User::all()->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),
                ]),
            Forms\Components\Toggle::make('status')
                ->label(__('Active'))
                ->onColor('success')
                ->offColor('danger')
                ->required(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('Full Name'))
                    ->getStateUsing(function ($record) {
                        return $record->first_name.' '.
                            $record->father_name.' '.
                            $record->grandfather_name.' '.
                            $record->family_name;
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label(__('First Name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('father_name')
                    ->label(__('Father Name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('grandfather_name')
                    ->label(__('Grandfather Name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Tables\Columns\TextColumn::make('leaveBalances_sum_balance')
                //     ->label('إجمالي رصيد الإجازات')
                //     ->sortable()
                //     ->getStateUsing(function ($record) {
                //         return $record->leaveBalances->sum('balance');
                //     })
                //     ->default('0') // القيمة الافتراضية في حال لم يكن هناك رصيد
                //     ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('leaveBalances')
                    ->label('رصيد الإجازات السنوية')
                    ->getStateUsing(function ($record) {
                        // الحصول على الإجازة السنوية
                        $leaveBalance = $record->leaveBalances->where('leave_type', 'annual')->first();

                        if ($leaveBalance) {
                            return $leaveBalance->calculateAnnualLeaveBalance();
                        }

                        return 'غير متوفر';
                    })
                    ->sortable()
                    ->default('غير متوفر')
                    ->toggleable(isToggledHiddenByDefault: false), // إذا لم يكن هناك رصيد

                Tables\Columns\TextColumn::make('family_name')
                    ->label(__('Family Name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label(__('Birth Date'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('national_id')
                    ->label(__('National ID'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('national_id_expiry')
                    ->label(__('National ID Expiry'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('nationality')
                    ->label(__('Nationality'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('bank_account')
                    ->label(__('Bank Account'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sponsor_company')
                    ->label(__('Sponsor Company'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('insuranceCompany.name')
                    ->label(__('Insurance Company'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('blood_type')
                    ->label(__('Blood Type'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('contract_start')
                    ->label(__('Contract Start'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('contract_end')
                    ->label(__('Contract End'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('actual_start')
                    ->label(__('Actual Start'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('basic_salary')
                    ->label(__('Basic Salary'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('living_allowance')
                    ->label(__('Living Allowance'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('other_allowances')
                    ->label(__('Other Allowances'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('job_status')
                    ->label(__('Job Status'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('health_insurance_status')
                    ->label(__('Health Insurance Status'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('health_insurance_company')
                    ->label(__('Health Insurance Company'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('social_security')
                    ->label(__('Social Security'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('social_security_code')
                    ->label(__('Social Security Code'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('qualification')
                    ->label(__('Qualification'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('specialization')
                    ->label(__('Specialization'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('mobile_number')
                    ->label(__('Mobile Number'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label(__('Phone Number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('region')
                    ->label(__('Region'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('city')
                    ->label(__('City'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('street')
                    ->label(__('Street'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('building_number')
                    ->label(__('Building Number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('apartment_number')
                    ->label(__('Apartment Number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('postal_code')
                    ->label(__('Postal Code'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('facebook')
                    ->label(__('Facebook'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('twitter')
                    ->label(__('Twitter'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('linkedin')
                    ->label(__('LinkedIn'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('password')
                    ->label(__('Password'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('added_by.name')
                    ->label(__('Added By'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BooleanColumn::make('status')
                    ->label(__('Status'))
                    ->sortable(),

            ])

            ->filters([
                SelectFilter::make('added_by')
                    ->label(__('Added By'))
                    ->options(User::all()->pluck('name', 'id')),

                // فلتر الموظفين الذين لديهم تأمين أو ليس لديهم
                Filter::make('with_insurance')
                    ->label(__('بالتأمين'))
                    ->query(fn ($query) => $query->whereNotNull('insurance_company_id')),

                Filter::make('without_insurance')
                    ->label(__('بدون تأمين'))
                    ->query(fn ($query) => $query->whereNull('insurance_company_id')),

                // فلتر حسب شركات التأمين
                SelectFilter::make('insurance_company_id')
                    ->label(__('شركة التأمين'))
                    ->relationship('insuranceCompany', 'name') // الربط مع جدول شركات التأمين
                    ->placeholder(__('كل الشركات')), // الخيار الافتراضي
            ])
            ->actions([

                Action::make('viewMap')
                    ->label('عرض المسار')
                    ->color('primary')
                    ->icon('heroicon-o-map')
                    ->url(fn ($record) => route('filament.pages.employee-paths', ['employeeId' => $record->id])),

                Tables\Actions\Action::make('view')
                    ->label(__('View'))
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => static::getUrl('view', ['record' => $record->id]))
                    ->openUrlInNewTab(false),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make(),
                BulkAction::make('sendNotification')
                    ->label('إرسال إشعار')
                    ->form([
                        Forms\Components\TextInput::make('title')
                            ->label('العنوان')
                            ->required()
                            ->placeholder('أدخل عنوان الإشعار'),
                        Forms\Components\Textarea::make('message')
                            ->label('الموضوع')
                            ->required()
                            ->placeholder('أدخل موضوع الإشعار'),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        // استخدام السجلات لإرسال الإشعارات
                        // جمع قائمة بالمعرفات (external_user_ids) من السجلات
                        // جمع قائمة بالمعرفات (external_user_ids) كـ Strings
                        $externalUserIds = $records->pluck('id')->filter()->map(fn ($id) => (string) $id)->toArray(); // تحويل إلى نصوص
                        Log::info('External User IDs:', $externalUserIds);

                        if (! empty($externalUserIds)) {
                            // إعداد الهيدرز
                            $headers = [
                                'Authorization' => 'Basic '.env('ONESIGNAL_REST_API_KEY'),
                                'Content-Type' => 'application/json; charset=utf-8',
                            ];

                            // إعداد بيانات الإشعار
                            $payload = [
                                'app_id' => env('ONESIGNAL_APP_ID'),
                                'include_external_user_ids' => $externalUserIds,
                                'headings' => ['en' => $data['title']],
                                'contents' => ['en' => $data['message']],
                            ];
                            // إرسال الطلب
                            $response = Http::withHeaders($headers)->post('https://onesignal.com/api/v1/notifications', $payload);

                            // تسجيل الاستجابة
                            Log::info('OneSignal Response:', [
                                'external_user_ids' => $externalUserIds,
                                'response' => $response->json(),
                            ]);
                        } else {
                            Log::warning('No valid external_user_ids found for sending notifications.');
                        }

                    })
                    ->requiresConfirmation()
                    ->color('primary'),
                // Tables\Actions\BulkAction::make('exportAll')
                // ->label(__('Export All to PDF'))
                // ->icon('heroicon-o-document-download')
                // ->action(function () {
                //     return ExcelExport::make()
                //         ->table('employees') // اسم الجدول في قاعدة البيانات
                //         ->columns([
                //             'first_name' => __('First Name'),
                //             'family_name' => __('Family Name'),
                //             'national_id' => __('National ID'),
                //             'job_status' => __('Job Status'),
                //             'email' => __('Email'),
                //         ])
                //         ->filename('all_employees.pdf')
                //         ->pdf();
                // }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),

            // 'view' => Pages\ViewEmployee::route('/{record}/view'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
            'view' => Pages\ViewEmployee::route('/{record}/view'),

        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProjectRecordsRelationManager::class,
            RelationManagers\AttachmentsRelationManager::class,
            RelationManagers\AttendancesRelationManager::class,
            RelationManagers\DevicesRelationManager::class,
            RelationManagers\LoansRelationManager::class,
            RelationManagers\ResignationsRelationManager::class,

        ];
    }

    //     protected function getHeaderWidgets(): array
    // {
    //     return [
    //         Widgets\ExpiringContracts::class,
    //     ];
    // }

}
