<?php

namespace App\Filament\Resources;

use App\Enums\ContractType;
use App\Enums\InsuranceType;
use App\Enums\MaritalStatus;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use App\Models\Exclusion;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = -1;

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
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make(__('Personal Information'))
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label(__('Identifier (leave empty to auto-generate)'))
                            ->visible(fn (string $context): bool => $context === 'create'),
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

                        Forms\Components\TextInput::make('english_name')
                            ->label(__('Full English Name'))
                            ->nullable(),

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
                        Forms\Components\Select::make('marital_status')
                            ->label(__('Marital Status')) // استخدام الترجمة
                            ->options(collect(MaritalStatus::cases())->mapWithKeys(fn ($status) => [
                                $status->value => $status->label(),
                            ]))
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('job_title')
                            ->label(__('Job Title'))
                            ->options(
                                collect(\App\Enums\JobTitle::cases())
                                    ->mapWithKeys(fn ($jobTitle) => [$jobTitle->value => $jobTitle->label()])
                                    ->toArray()
                            )
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('bank_name')
                            ->label(__('Bank Name'))
                            ->options(
                                collect(\App\Enums\Bank::cases())
                                    ->mapWithKeys(fn ($bank) => [$bank->value => $bank->label()])
                                    ->toArray()
                            )
                            ->required(),

                        Forms\Components\TextInput::make('bank_account')
                            ->label(__('Bank Account'))
                            ->required(),

                        Forms\Components\Select::make('blood_type')
                            ->label(__('Blood Type'))
                            ->options(
                                collect(\App\Enums\BloodType::cases())
                                    ->mapWithKeys(fn ($bloodType) => [$bloodType->value => $bloodType->label()])
                                    ->toArray()
                            )
                            ->required()
                            ->searchable(),
                    ])

                    ->columns(2),

                // Social Insurance (Wizard)
                Forms\Components\Wizard\Step::make(__('COSI Information'))
                    ->schema([
                        Forms\Components\Select::make('insurance_type')
                            ->label(__('Social Insurance Type'))
                            ->options(InsuranceType::options())
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state === 'commercial_record') {
                                    $set('insurance_company_id', null); // إعادة تعيين القيمة
                                }
                            }),

                        // Forms\Components\Select::make('insurance_company_id')
                        //     ->label(__('Insurance Company'))
                        //     ->relationship('commercialRecord', 'entity_name')
                        //     ->visible(fn ($get) => $get('insurance_type') === 'commercial_record')
                        //     ->required(),
                        Forms\Components\Select::make('commercial_record_id')
                            ->label(__('Commercial Record'))
                            ->relationship('commercialRecord', 'entity_name') // ربط العلاقة مع السجلات التجارية
                            ->required()
                            ->searchable()
                            ->placeholder(__('Select Commercial Record'))
                            ->preload()->visible(fn ($get) => $get('insurance_type') === 'commercial_record'),

                        // Forms\Components\TextInput::make('insurance_company_name')
                        //     ->label(__('Insurance Company Name'))
                        //     ->nullable()
                        //     ->maxLength(255)->visible(fn ($get) => $get('insurance_type') === 'commercial_record'),

                        Forms\Components\TextInput::make('insurance_number')
                            ->label(__('Subscriber number'))
                            ->visible(fn ($get) => $get('insurance_type') === 'commercial_record'),

                        Forms\Components\DatePicker::make('insurance_start_date')
                            ->label(__('Insurance registration date'))->visible(fn ($get) => $get('insurance_type') === 'commercial_record'),

                        Forms\Components\DatePicker::make('insurance_end_date')
                            ->label(__('Date of exclusion from insurance'))->visible(fn ($get) => $get('insurance_type') === 'commercial_record'),

                        Forms\Components\Select::make('insurance_company_id')
                            ->label(__('Insurance Company M'))
                            ->relationship('insuranceCompany', 'name') // ربط العلاقة مع جدول شركات التأمين
                            ->options(function () {
                                return \App\Models\InsuranceCompany::pluck('name', 'id')->prepend('لا توجد شركة تأمين', '');
                            }) // إضافة خيار لتصفير القيمة
                            ->placeholder('اختر شركة التأمين') // النص الافتراضي
                            ->nullable() // السماح للحقل بأن يكون فارغًا
                            ->searchable() // دعم البحث
                            ->preload()->visible(fn ($get) => $get('insurance_type') === 'commercial_record'),
                        Forms\Components\Select::make('parent_insurance')
                            ->label(__('Parents Insurance'))
                            ->options(
                                collect(\App\Enums\ParentInsurance::cases())
                                    ->mapWithKeys(fn ($insurance) => [$insurance->value => $insurance->label()])
                                    ->toArray()
                            )
                            ->nullable()
                            ->searchable()->visible(fn ($get) => $get('insurance_type') === 'commercial_record'),
                    ])
                    ->columns(2),

                // Job Information
                Forms\Components\Wizard\Step::make(__('Job Information'))
                    ->schema([
                        Forms\Components\Select::make('contract_type')
                            ->label(__('Contract Type'))
                            ->reactive()
                            ->options(ContractType::options())
                            ->required(),

                        Forms\Components\DatePicker::make('contract_start')
                            ->label(__('Contract Start')),

                        Forms\Components\DatePicker::make('contract_end')
                            ->label(__('Contract End'))
                            ->minDate(now()) // لضمان اختيار تاريخ مستقبلي
                            ->displayFormat('Y-m-d')
                            ->placeholder(__('Select contract end date'))
                            ->visible(fn ($get) => $get('contract_type') === 'limited'),

                        Forms\Components\DatePicker::make('actual_start')
                            ->label(__('Actual Start'))
                        // ->required()
                        ,

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
                            ->label(__('Job Status')),

                        // Forms\Components\TextInput::make('health_insurance_status')
                        //     ->label(__('Health Insurance Status'))
                        //     ->required(),

                        // Forms\Components\TextInput::make('health_insurance_company')
                        //     ->label(__('Health Insurance Company')),

                        // Forms\Components\TextInput::make('social_security')
                        //     ->label(__('Social Security')),

                        // Forms\Components\TextInput::make('social_security_code')
                        //     ->label(__('Social Security Code')),
                    ])->columns(2),

                // Education
                Forms\Components\Wizard\Step::make(__('Education'))
                    ->schema([
                        Forms\Components\TextInput::make('qualification')
                            ->label(__('Qualification'))
                            ->required(),

                        Forms\Components\TextInput::make('specialization')
                            ->label(__('Specialization'))
                            ->required(),
                    ])->columns(2),

                // Contact Information
                Forms\Components\Wizard\Step::make(__('Contact Information'))
                    ->schema([
                        Forms\Components\TextInput::make('mobile_number')
                            ->label(__('Mobile Number'))
                            ->required(),

                        Forms\Components\TextInput::make('phone_number')
                            ->label(__('Phone Number')),

                        Forms\Components\TextInput::make('email')
                            ->label(__('Email'))
                            ->email(),
                    ])->columns(2),

                // Address
                Forms\Components\Wizard\Step::make(__('Address'))
                    ->schema([
                        Forms\Components\TextInput::make('region')
                            ->label(__('Region'))
                            ->required(),

                        Forms\Components\TextInput::make('city')
                            ->label(__('City'))
                            ->required(),

                        Forms\Components\TextInput::make('street')
                            ->label(__('Street')),

                        Forms\Components\TextInput::make('building_number')
                            ->label(__('Building Number')),

                        Forms\Components\TextInput::make('apartment_number')
                            ->label(__('Apartment Number')),

                        Forms\Components\TextInput::make('postal_code')
                            ->label(__('Postal Code')),
                    ])->columns(2),

                // Social Media
                Forms\Components\Wizard\Step::make(__('Social Media'))
                    ->schema([
                        Forms\Components\TextInput::make('facebook')
                            ->label(__('Facebook')),

                        Forms\Components\TextInput::make('twitter')
                            ->label(__('Twitter')),

                        Forms\Components\TextInput::make('linkedin')
                            ->label(__('LinkedIn')),
                    ])->columns(2),
                Forms\Components\Wizard\Step::make(__('Leave Balances'))
                    ->schema([
                        Repeater::make('leaveBalances')
                            ->relationship('leaveBalances')
                            // ->defaultItems(1)
                            // ->required()
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
                                // ->disabled()
                                , // لا يمكن تحريره يدويًا
                                TextInput::make('accrued_per_month')
                                    ->label('المستحق شهريًا')
                                    ->numeric(),
                                TextInput::make('used_balance')
                                    ->label('المستخدم')
                                    ->numeric(),
                            ])
                            ->label('رصيد الإجازات')
                            ->columns(4), // تحديث عدد الأعمدة ليشمل الحقل الجديد
                    ]),

                // Security
                Forms\Components\Wizard\Step::make(__('Security'))
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label(__('Password'))
                            ->password()
                            ->required(),

                        Forms\Components\Select::make('added_by')
                            ->label(__('Added By'))
                            ->options(User::all()->pluck('name', 'id'))
                            ->default(auth()->user()->id)
                            ->searchable()
                            ->disabled()
                            ->nullable(),
                    ]),

            ])
                ->skippable(),

            Forms\Components\Toggle::make('status')
                ->label(__('Active'))
                ->onColor('success')
                ->offColor('danger')
                ->default(true)
                ->required(),

        ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('Full Name'))
                    ->getStateUsing(function ($record) {
                        return $record->first_name.' '.
                            $record->father_name.' '.
                            $record->grandfather_name.' '.
                            $record->family_name;
                    })
                    // ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('current_zone')
                    ->label(__('Current Zone'))
                    ->getStateUsing(function ($record) {
                        $currentZone = $record->currentZone; // استدعاء العلاقة الحالية

                        return $currentZone ? $currentZone->name : __('Not Assigned');
                    })
                    ->copyable()
                    ->copyMessageDuration(1500)
                    ->sortable()
                    // ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('latest_project')
                    ->label(__('Latest Project'))
                    ->getStateUsing(function ($record) {
                        $currentProjectRecord = $record->latestZone; // استدعاء العلاقة الحالية

                        return $currentProjectRecord ? $currentProjectRecord->name : __('Not Assigned');
                    })
                    ->sortable()
                    ->copyable()
                    ->copyMessageDuration(1500)
                // ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('current_project')
                    ->label(__('Current Project'))
                    ->getStateUsing(function ($record) {
                        $currentProjectRecord = $record->currentProjectRecord; // استدعاء العلاقة الحالية

                        return $currentProjectRecord ? $currentProjectRecord->project->name : __('Not Assigned');
                    })
                    ->copyable()
                    ->copyMessageDuration(1500)
                    ->sortable()
                    // ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('first_name')
                    ->label(__('First Name'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('father_name')
                    ->label(__('Father Name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('grandfather_name')
                    ->label(__('Grandfather Name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('family_name')
                    ->label(__('Family Name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('english_name')
                    ->label(__('English Name'))
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
                    ->toggleable(isToggledHiddenByDefault: true), // إذا لم يكن هناك رصيد

                Tables\Columns\TextColumn::make('is_excluded')
                    ->badge()
                    ->label(__('Excluded'))
                    ->getStateUsing(fn ($record) => $record->isExcluded() ? __('Yes') : __('No'))
                    ->color(fn ($record) => $record->isExcluded() ? 'danger' : 'success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('birth_date')
                    ->label(__('Birth Date'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('national_id')
                    ->label(__('National ID'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('marital_status')
                    ->label(__('Marital Status'))
                    ->formatStateUsing(fn ($state) => $state ? MaritalStatus::fromArabic($state)?->label() ?? '-' : '-'
                    )
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('national_id_expiry')
                    ->label(__('National ID Expiry'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('nationality')
                    ->label(__('Nationality'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('bank_account')
                    ->label(__('Bank Account'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Tables\Columns\TextColumn::make('sponsor_company')
                //     ->label(__('Sponsor Company'))
                //     ->searchable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('parent_insurance')
                    ->label(__('Parents Insurance'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('insurance_company_name')
                    ->label(__('Insurance Company Name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('commercialRecord.entity_name')
                    ->label(__('Commercial Record'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('job_title')
                    ->label(__('Job Title'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('bank_name')
                    ->label(__('Bank Name'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('insurance_type')
                    ->label(__('Social Insurance Type'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('insurance_number')
                    ->label(__('Insurance Number'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('insurance_start_date')
                    ->label(__('Insurance Start Date'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('insurance_end_date')
                    ->label(__('Insurance End Date'))
                    ->date('Y-m-d')
                    ->sortable()
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

                // Tables\Columns\TextColumn::make('health_insurance_status')
                //     ->label(__('Health Insurance Status'))
                //     ->searchable()
                //     ->toggleable(isToggledHiddenByDefault: true),

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

                Tables\Columns\TextColumn::make('addedBy.name')
                    ->label(__('Added By'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BooleanColumn::make('status')
                    ->label(__('Status'))
                    ->sortable(),

            ])
            ->paginationPageOptions([10, 25, 50, 100])

            ->filters([

                Tables\Filters\Filter::make('excluded')
                    ->label(__('Excluded Employees'))
                    ->query(fn ($query) => $query->whereHas('exclusions', fn ($q) => $q->where('status', Exclusion::STATUS_APPROVED)))
                    ->toggle(),
                SelectFilter::make('added_by')
                    ->label(__('Added By'))
                    ->options(User::all()->pluck('name', 'id')),

                // فلتر الموظفين الذين لديهم تأمين أو ليس لديهم
                Filter::make('with_insurance')
                    ->label(__('With Insurance'))
                    ->query(fn ($query) => $query->whereNotNull('insurance_company_id')),

                Filter::make('without_insurance')
                    ->label(__('Without Insurance'))
                    ->query(fn ($query) => $query->whereNull('insurance_company_id')),

                // فلتر حسب شركات التأمين الطبي
                SelectFilter::make('insurance_company_id')
                    ->label(__('Medical Insurance Company'))
                    ->relationship('insuranceCompany', 'name')
                    ->placeholder(__('All Companies'))
                    ->searchable(),

                // فلتر حسب شركة السجل التجاري
                SelectFilter::make('commercial_record_id')
                    ->label(__('Commercial Record'))
                    ->relationship('commercialRecord', 'entity_name')
                    ->placeholder(__('All Records'))
                    ->searchable(),

                // فلتر حسب المسمى الوظيفي
                SelectFilter::make('job_title')
                    ->label(__('Job Title'))
                    ->options(
                        collect(\App\Enums\JobTitle::cases())
                            ->mapWithKeys(fn ($jobTitle) => [$jobTitle->value => $jobTitle->label()])
                            ->toArray()
                    )
                    ->placeholder(__('All Job Titles'))
                    ->searchable(),

                // فلتر حسب البنك
                SelectFilter::make('bank_name')
                    ->label(__('Bank Name'))
                    ->options(
                        collect(\App\Enums\Bank::cases())
                            ->mapWithKeys(fn ($bank) => [$bank->value => $bank->label()])
                            ->toArray()
                    )
                    ->placeholder(__('All Banks'))
                    ->searchable(),

                // فلتر حسب فصيلة الدم
                SelectFilter::make('blood_type')
                    ->label(__('Blood Type'))
                    ->options(
                        collect(\App\Enums\BloodType::cases())
                            ->mapWithKeys(fn ($bloodType) => [$bloodType->value => $bloodType->label()])
                            ->toArray()
                    )
                    ->placeholder(__('All Blood Types'))
                    ->searchable(),

                // فلتر حسب حالة التأمين الاجتماعي
                Filter::make('with_social_insurance')
                    ->label(__('With Social Insurance'))
                    ->query(fn ($query) => $query->whereNotNull('insurance_number')),

                Filter::make('without_social_insurance')
                    ->label(__('Without Social Insurance'))
                    ->query(fn ($query) => $query->whereNull('insurance_number')),

                // فلتر حسب تأمين الوالدين
                SelectFilter::make('parent_insurance')
                    ->label(__('Parents Insurance'))
                    ->options(
                        collect(\App\Enums\ParentInsurance::cases())
                            ->mapWithKeys(fn ($insurance) => [$insurance->value => $insurance->label()])
                            ->toArray()
                    )
                    ->placeholder(__('All Parents Insurance Options'))
                    ->searchable(),
                Filter::make('contract_start')
                    ->label(__('Contract Started After'))
                    ->form([
                        Forms\Components\DatePicker::make('start_date')->label(__('Start Date')),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when(! empty($data['start_date']), fn ($query) => $query->where('contract_start', '>=', $data['start_date']))),

                Filter::make('contract_end')
                    ->label(__('Contract Ends Before'))
                    ->form([
                        Forms\Components\DatePicker::make('end_date')->label(__('End Date')),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when(! empty($data['end_date']), fn ($query) => $query->where('contract_end', '<=', $data['end_date']))),

                // فلتر حسب الراتب الأساسي
                Filter::make('basic_salary')
                    ->label(__('Basic Salary Range'))
                    ->form([
                        Forms\Components\TextInput::make('min_salary')->label(__('Minimum Salary'))->numeric(),
                        Forms\Components\TextInput::make('max_salary')->label(__('Maximum Salary'))->numeric(),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['min_salary'], fn ($query, $min) => $query->where('basic_salary', '>=', $min))
                        ->when($data['max_salary'], fn ($query, $max) => $query->where('basic_salary', '<=', $max))),

            ])
            ->actions([
                Tables\Actions\BulkActionGroup::make([
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
                    Tables\Actions\Action::make('exportYearly')
                        ->label('تصدير الحضور السنوي')
                    // ->icon('heroicon-o-document-download')
                        ->action(function ($record, array $data) {
                            // الحصول على السنة المُدخلة في النموذج
                            $year = $data['year'];
                            // إنشاء رابط مؤقت لدالة التصدير مع تمرير معرّف الموظف والسنة
                            $url = URL::temporarySignedRoute(
                                'export.attendance.yearly', // تأكد من تعريف هذا الاسم في ملف routes
                                now()->addMinutes(5),
                                [
                                    'employee_id' => $record->id,
                                    'year' => $year,
                                ]
                            );

                            return redirect($url);
                        })
                        ->form([
                            Forms\Components\Select::make('year')
                                ->label('السنة')
                                ->options(array_combine(range(2024, 2035), range(2024, 2035)))
                                ->default(date('Y') >= 2024 && date('Y') <= 2035 ? date('Y') : 2024)
                                ->required(),
                        ]),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
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
                        Forms\Components\Select::make('type')
                            ->label('نوع الإشعار')
                            ->options([
                                'general' => 'تعميم',
                                'notification' => 'إخطار',
                                'warning' => 'إنذار',
                                'violation' => 'مخالفة',
                                'summons' => 'استدعاء',
                                'other' => 'أخرى',
                            ])
                            ->required()
                            ->placeholder('اختر نوع الإشعار'),
                        Forms\Components\FileUpload::make('attachment')
                            ->label('المرفقات')
                            ->disk('s3')
                            ->visibility('public')
                            ->directory('notifications/attachments')
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->placeholder('أرفق ملف (صورة أو وثيقة) إذا لزم الأمر'),
                        Forms\Components\Checkbox::make('send_via_whatsapp')
                            ->label('إرسال عبر WhatsApp')
                            ->default(false),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        // جمع قائمة بالمعرفات (external_user_ids) كـ Strings
                        $externalUserIds = $records->pluck('id')->filter()->map(fn ($id) => (string) $id)->toArray();
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
                                'data' => [
                                    'type' => $data['type'],
                                ],
                            ];

                            // إضافة المرفق إذا كان صورة
                            if (! empty($data['attachment'])) {
                                // $attachmentUrl = asset('storage/' . $data['attachment']); // تحويل المسار إلى URL
                                $attachmentUrl = Storage::disk('s3')->url($data['attachment']); // تحويل المسار إلى URL
                                $mimeType = \Illuminate\Support\Facades\Storage::disk('s3')->mimeType($data['attachment']);
                                if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
                                    // إرسال الصورة مع الإشعار عبر OneSignal باستخدام المفتاح big_picture
                                    $payload['big_picture'] = $attachmentUrl;
                                }
                                // $payload['big_picture'] = $attachmentUrl; // حقل الصورة في OneSignal
                                $payload['data']['attachment_url'] = $attachmentUrl; // تخزين المسار في البيانات

                                //   dd($attachmentUrl);
                            }

                            // إرسال الطلب
                            $response = Http::withHeaders($headers)->post('https://onesignal.com/api/v1/notifications', $payload);

                            // // تسجيل الاستجابة
                            // Log::info('OneSignal Response:', [
                            //     'external_user_ids' => $externalUserIds,
                            //     'response' => $response->json(),
                            // ]);
                        } else {
                            Log::warning('No valid external_user_ids found for sending notifications.');
                        }

                        foreach ($records as $employee) {
                            // إعداد المرفق URL
                            $attachmentUrl = ! empty($data['attachment']) ? Storage::disk('s3')->url($data['attachment']) : null;

                            // حفظ الإشعار في قاعدة البيانات
                            \App\Models\EmployeeNotification::create([
                                'employee_id' => $employee->id,
                                'type' => $data['type'],
                                'title' => $data['title'],
                                'message' => $data['message'],
                                'attachment' => $attachmentUrl,
                                'sent_via_whatsapp' => $data['send_via_whatsapp'],
                            ]);

                            // إرسال الإشعار عبر WhatsApp إذا تم تحديد الخيار
                            if ($data['send_via_whatsapp']) {
                                $phone = $employee->mobile_number;
                                if ($phone) {
                                    $attachmentBase64 = null;
                                    $fileName = null;
                                    if (! empty($data['attachment'])) {
                                        // التأكد من وجود الملف على S3 ثم تحويل محتواه إلى base64 واستخراج اسم الملف
                                        if (\Illuminate\Support\Facades\Storage::disk('s3')->exists($data['attachment'])) {
                                            $fileContent = \Illuminate\Support\Facades\Storage::disk('s3')->get($data['attachment']);
                                            $attachmentBase64 = base64_encode($fileContent);
                                            $fileName = pathinfo($data['attachment'], PATHINFO_BASENAME);
                                        }
                                    }

                                    // استدعاء خدمة OtpService لإرسال الإشعار عبر WhatsApp مع المرفق (صورة أو ملف)
                                    $otpService = new \App\Services\OtpService;
                                    $otpService->sendViaWhatsappWithAttachment(
                                        $phone,
                                        $data['type'],
                                        $data['title'],
                                        $data['message'],
                                        $attachmentBase64,
                                        $fileName
                                    );
                                    $otpService->sendViaWhatsappWithAttachment(
                                        '120363419182449313@g.us',
                                        $data['type'],
                                        $data['title'],
                                        $data['message'],
                                        $attachmentBase64,
                                        $fileName
                                    );
                                }
                            }
                        }

                    })
                    ->requiresConfirmation()
                    ->color('primary'),

                BulkAction::make('exportAttendance')
                    ->label('تصدير الحضور')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('تاريخ البداية')
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('تاريخ النهاية')
                            ->required(),
                    ])
                    ->action(function (array $data, $records) {
                        $employeeIds = $records->pluck('id')->toArray();

                        // إنشاء رابط مؤقت لاستدعاء التصدير وتمرير معرّفات الموظفين
                        $url = URL::temporarySignedRoute(
                            'export.attendance.filtered',
                            now()->addMinutes(5),
                            [
                                'employee_ids' => implode(',', $employeeIds),
                                'start_date' => $data['start_date'],
                                'end_date' => $data['end_date'],
                            ]
                        );

                        return redirect($url);
                    })
                    ->modalSubmitActionLabel('تصدير')
                // ->modalTitle('تصدير الحضور')
                // ->modalButton('تصدير')           // اسم زر الإرسال
                ,

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
            RelationManagers\RequestsRelationManager::class,
            RelationManagers\AssetAssignmentsRelationManager::class,

        ];
    }

    //     protected function getHeaderWidgets(): array
    // {
    //     return [
    //         Widgets\ExpiringContracts::class,
    //     ];
    // }

}
