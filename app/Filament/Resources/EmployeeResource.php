<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;



use App\Models\User;




use Filament\Tables\Filters\SelectFilter;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

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
                        ->label(__('Grandfather Name')),
    
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
    
                    Forms\Components\TextInput::make('blood_type')
                        ->label(__('Blood Type')),
                ]),
    
            // Job Information
            Forms\Components\Fieldset::make(__('Job Information'))
                ->schema([
                    Forms\Components\DatePicker::make('contract_start')
                        ->label(__('Contract Start'))
                        ->required(),
                        Forms\Components\DatePicker::make('contract_end')
    ->label(__('Contract End'))
    ->required()
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
                        ->label(__('Job Status')),
    
                    Forms\Components\TextInput::make('health_insurance_status')
                        ->label(__('Health Insurance Status')),
    
                    Forms\Components\TextInput::make('health_insurance_company')
                        ->label(__('Health Insurance Company')),
    
                    Forms\Components\TextInput::make('vacation_balance')
                        ->label(__('Vacation Balance'))
                        ->numeric(),
    
                    Forms\Components\TextInput::make('social_security')
                        ->label(__('Social Security')),
    
                    Forms\Components\TextInput::make('social_security_code')
                        ->label(__('Social Security Code')),
                ]),
    
            // Education
            Forms\Components\Fieldset::make(__('Education'))
                ->schema([
                    Forms\Components\TextInput::make('qualification')
                        ->label(__('Qualification')),
    
                    Forms\Components\TextInput::make('specialization')
                        ->label(__('Specialization')),
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
                        ->label(__('Street')),
    
                    Forms\Components\TextInput::make('building_number')
                        ->label(__('Building Number')),
    
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
                    return $record->first_name . ' ' .
                           $record->father_name . ' ' .
                           $record->grandfather_name . ' ' .
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
    
                Tables\Columns\TextColumn::make('vacation_balance')
                    ->label(__('Vacation Balance'))
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
            ])
            ->actions([
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
        RelationManagers\AttendanceRelationManager::class,
        RelationManagers\DevicesRelationManager::class,
        RelationManagers\LoanRelationManager::class,
    
    ];
}

//     protected function getHeaderWidgets(): array
// {
//     return [
//         Widgets\ExpiringContracts::class,
//     ];
// }

    
}
