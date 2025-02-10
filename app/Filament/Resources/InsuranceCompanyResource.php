<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InsuranceCompanyResource\Pages;
use App\Models\InsuranceCompany;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InsuranceCompanyResource extends Resource
{
    protected static ?string $model = InsuranceCompany::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    // protected static ?string $navigationGroup = 'الإعدادات';
    // protected static ?string $label = 'شركة التأمين';
    // protected static ?string $pluralLabel = 'شركات التأمين';
    public static function getNavigationLabel(): string
    {
        return __('Insurance Companies');
    }

    public static function getPluralLabel(): string
    {
        return __('Insurance Companies');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Business & License Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('CommercialRecordTabs')
                    ->tabs([
                        // 📌 تبويب المعلومات الأساسية
                        Forms\Components\Tabs\Tab::make(__('Basic Details'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('اسم الشركة'))
                                    ->required()
                                    ->maxLength(255),
                                DatePicker::make('activation_date')
                                    ->label(__('تاريخ التفعيل'))
                                    ->required(),
                                DatePicker::make('expiration_date')
                                    ->label(__('تاريخ الانتهاء'))
                                    ->required(),
                                TextInput::make('policy_number')
                                    ->label(__('رقم الوثيقة'))
                                    ->required(),
                                TextInput::make('branch')
                                    ->label(__('الفرع'))
                                    ->required()
                                    ->maxLength(255),
                                Toggle::make('is_active')
                                    ->label(__('مفعل'))
                                    ->default(true) // القيمة الافتراضية
                                    ->inline(false), // عرض الحقل كمفتاح تبديل

                            ])->columns(2),

                        // 📌 تبويب المرفقات
                        Forms\Components\Tabs\Tab::make(__('Attachments'))
                            ->schema([
                                Forms\Components\Repeater::make('recordMedia')
                                    ->relationship('recordMedia')
                                    ->schema([
                                        Forms\Components\TextInput::make('title')
                                            ->label(__('Title'))
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('notes')
                                            ->label(__('Notes'))
                                            ->nullable()
                                            ->rows(2),

                                        Forms\Components\DatePicker::make('expiry_date')
                                            ->label(__('Expiry Date'))
                                            ->nullable(),

                                        Forms\Components\SpatieMediaLibraryFileUpload::make('file')
                                            ->label(__('Upload File'))
                                            ->collection('record_media')
                                            ->multiple()
                                            ->disk('s3') // ✅ رفع الملفات مباشرة إلى S3
                                            // ->preserveFilenames()
                                            ->maxFiles(5)
                                            ->maxSize(10240), // 10MB
                                    ])

                                    ->collapsible()
                                    ->grid(2)
                                    ->columns(2)
                                    ->default([]),
                            ]),
                    ]),
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
                ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->label(__('اسم الشركة'))->sortable()->searchable(),
                TextColumn::make('activation_date')->label(__('تاريخ التفعيل'))->sortable(),
                TextColumn::make('expiration_date')->label(__('تاريخ الانتهاء'))->sortable(),
                TextColumn::make('policy_number')->label(__('رقم الوثيقة'))->sortable(),
                TextColumn::make('branch')->label(__('الفرع'))->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('مفعل'))
                    ->sortable(),
                // BooleanColumn::make('is_active')
                // ->label(__('مفعل')) // يعرض حالة التفعيل
                // ->trueIcon('heroicon-o-check-circle') // أيقونة عند التفعيل
                // ->falseIcon('heroicon-o-x-circle') // أيقونة عند التعطيل
                // ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label(__('الشركات النشطة'))
                    ->query(fn ($query) => $query->where('expiration_date', '>=', now())),
                Tables\Filters\Filter::make('active_only')
                    ->label(__('الشركات المفعلة'))
                    ->query(fn ($query) => $query->where('is_active', true)),
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
            'index' => Pages\ListInsuranceCompanies::route('/'),
            'create' => Pages\CreateInsuranceCompany::route('/create'),
            'edit' => Pages\EditInsuranceCompany::route('/{record}/edit'),
        ];
    }
}
