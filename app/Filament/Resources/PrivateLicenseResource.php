<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrivateLicenseResource\Pages;
use App\Models\PrivateLicense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use MohamedSabil83\FilamentHijriPicker\Forms\Components\HijriDatePicker;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PrivateLicenseResource extends Resource
{
    protected static ?string $model = PrivateLicense::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

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
        return __('Private Licenses');
    }

    public static function getPluralLabel(): string
    {
        return __('Private Licenses');
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
                                Forms\Components\Select::make('commercial_record_id')
                                    ->label(__('Commercial Record'))
                                    ->relationship('commercialRecord', 'entity_name')
                                    ->required()
                                    ->preload()
                                    ->searchable(),

                                Forms\Components\TextInput::make('license_name')
                                    ->label(__('License Name'))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('license_number')
                                    ->label(__('License Number'))
                                    ->required()
                                    // ->unique()
                                    ->maxLength(50),

                                Forms\Components\DatePicker::make('issue_date')
                                    ->label(__('Issue Date'))
                                    ->nullable(),

                                Forms\Components\DatePicker::make('expiry_date')
                                    ->label(__('Expiry Date (Gregorian)'))
                                    ->nullable(),

                                HijriDatePicker::make('expiry_date_hijri')
                                    ->label(__('Expiry Date (Hijri)'))
                                    ->nullable(),

                                Forms\Components\TextInput::make('website')
                                    ->label(__('Website'))
                                    ->url()
                                    ->nullable(),

                                Forms\Components\TextInput::make('platform_username')
                                    ->label(__('Platform Username'))
                                    ->nullable(),

                                Forms\Components\TextInput::make('platform_password')
                                    ->label(__('Platform Password'))
                                    ->password()
                                    ->nullable(),

                                Forms\Components\TextInput::make('platform_user_id')
                                    ->label(__('Platform User ID'))
                                    ->nullable(),

                                Forms\Components\Textarea::make('description')
                                    ->label(__('Description'))
                                    ->nullable(),
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
                                            ->preserveFilenames()
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
                Tables\Columns\TextColumn::make('commercialRecord.entity_name')
                    ->label(__('Commercial Record'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('license_name')
                    ->label(__('License Name'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('license_number')
                    ->label(__('License Number'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label(__('Expiry Date (Gregorian)'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiry_date_hijri')
                    ->label(__('Expiry Date (Hijri)'))
                    ->date()
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('d/m/Y'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('website')
                    ->label(__('Website'))
                    ->url(fn ($record) => $record->website) // توفير الرابط من الحقل 'website'
                    ->toggleable(),

                Tables\Columns\TextColumn::make('platform_username')
                    ->label(__('Platform Username'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('platform_user_id')
                    ->label(__('Platform User ID'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('Description'))
                    ->toggleable()
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\Filter::make('expiry_soon')
                    ->label(__('Expiring Soon'))
                    ->query(fn (Builder $query) => $query->where('expiry_date', '<=', now()->addMonth())),

                Tables\Filters\Filter::make('no_description')
                    ->label(__('Without Description'))
                    ->query(fn (Builder $query) => $query->whereNull('description')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                ExportBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrivateLicenses::route('/'),
            'create' => Pages\CreatePrivateLicense::route('/create'),
            'edit' => Pages\EditPrivateLicense::route('/{record}/edit'),
        ];
    }
}
