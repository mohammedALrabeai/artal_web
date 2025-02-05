<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MunicipalLicenseResource\Pages;
use App\Models\MunicipalLicense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class MunicipalLicenseResource extends Resource
{
    protected static ?string $model = MunicipalLicense::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return __('Municipal Licenses');
    }

    public static function getPluralLabel(): string
    {
        return __('Municipal Licenses');
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

                                Forms\Components\TextInput::make('license_number')
                                    ->label(__('License Number B'))
                                    ->required()
                                    ->unique()
                                    ->maxLength(50),

                                Forms\Components\DatePicker::make('expiry_date_hijri')
                                    ->label(__('Expiry Date L(Hijri)'))
                                    ->nullable(),

                                Forms\Components\DatePicker::make('expiry_date_gregorian')
                                    ->label(__('Expiry Date L(Gregorian)'))
                                    ->nullable(),

                                // Forms\Components\TextInput::make('vat')
                                //     ->label(__('VAT'))
                                //     ->nullable()
                                //     ->maxLength(50),
                                Forms\Components\Textarea::make('notes')
                                    ->label(__('Notes')) // عمود الملاحظات
                                    ->nullable()
                                    ->maxLength(1000),
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

                Tables\Columns\TextColumn::make('license_number')
                    ->label(__('License Number B'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('expiry_date_hijri')
                    ->label(__('Expiry Date L(Hijri)'))
                    ->dateTime()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expiry_date_gregorian')
                    ->label(__('Expiry Date L(Gregorian)'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                // Tables\Columns\TextColumn::make('vat')
                //     ->label(__('VAT'))
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes')) // عرض الملاحظات
                    ->toggleable()
                    ->limit(50), // عرض أول 50 حرف فقط
            ])
            ->filters([
                Tables\Filters\Filter::make('expiry_soon')
                    ->label(__('Expiring Soon'))
                    ->query(fn (Builder $query) => $query->where('expiry_date_gregorian', '<=', now()->addMonth())),

                // Tables\Filters\Filter::make('no_vat')
                //     ->label(__('Without VAT'))
                //     ->query(fn (Builder $query) => $query->whereNull('vat')),
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
            'index' => Pages\ListMunicipalLicenses::route('/'),
            'create' => Pages\CreateMunicipalLicense::route('/create'),
            'edit' => Pages\EditMunicipalLicense::route('/{record}/edit'),
        ];
    }
}
