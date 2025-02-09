<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\PostalSubscription;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\PostalSubscriptionResource\Pages;
use MohamedSabil83\FilamentHijriPicker\Forms\Components\HijriDatePicker;

class PostalSubscriptionResource extends Resource
{
    protected static ?string $model = PostalSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    public static function getNavigationBadge(): ?string
    {
        // ✅ إخفاء العدد عن المستخدمين غير الإداريين
        if (!auth()->user()?->hasRole('admin')) {
            return null;
        }
    
        return static::getModel()::count();
    }
    

    public static function getNavigationLabel(): string
    {
        return __('Postal Subscriptions');
    }

    public static function getPluralLabel(): string
    {
        return __('Postal Subscriptions');
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

                                Forms\Components\TextInput::make('subscription_number')
                                    ->label(__('Subscription Number'))
                                    ->required()
                                    // ->unique()
                                    ->maxLength(50),

                                Forms\Components\DatePicker::make('start_date')
                                    ->label(__('Start Date'))
                                    ->nullable(),

                                Forms\Components\DatePicker::make('expiry_date')
                                    ->label(__('Expiry Date (Gregorian)'))
                                    ->nullable(),

                                    HijriDatePicker::make('expiry_date_hijri')
                                    ->label(__('Expiry Date (Hijri)')) // نهاية الاشتراك (هجري)
                                    ->nullable(),

                                Forms\Components\TextInput::make('mobile_number')
                                    ->label(__('Mobile Number')) // رقم الجوال
                                    ->nullable()
                                    ->tel(),

                                Forms\Components\Textarea::make('notes')
                                    ->label(__('Notes'))
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

                Tables\Columns\TextColumn::make('subscription_number')
                    ->label(__('Subscription Number'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('Start Date'))
                    ->dateTime()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label(__('Expiry Date (Gregorian)'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiry_date_hijri')
                    ->label(__('Expiry Date (Hijri)')) // نهاية الاشتراك (هجري)
                    ->date()
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('d/m/Y'))

                    ->sortable(),

                Tables\Columns\TextColumn::make('mobile_number')
                    ->label(__('Mobile Number')) // رقم الجوال
                    ->toggleable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->toggleable()
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\Filter::make('expiry_soon')
                    ->label(__('Expiring Soon'))
                    ->query(fn (Builder $query) => $query->where('expiry_date', '<=', now()->addMonth())),

                Tables\Filters\Filter::make('no_notes')
                    ->label(__('Without Notes'))
                    ->query(fn (Builder $query) => $query->whereNull('notes')),
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
            'index' => Pages\ListPostalSubscriptions::route('/'),
            'create' => Pages\CreatePostalSubscription::route('/create'),
            'edit' => Pages\EditPostalSubscription::route('/{record}/edit'),
        ];
    }
}
