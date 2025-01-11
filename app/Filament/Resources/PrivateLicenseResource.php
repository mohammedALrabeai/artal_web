<?php

namespace App\Filament\Resources;

use App\Models\PrivateLicense;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\PrivateLicenseResource\Pages;

class PrivateLicenseResource extends Resource
{
    protected static ?string $model = PrivateLicense::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function getNavigationBadge(): ?string
    {
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
                Forms\Components\Select::make('commercial_record_id')
                    ->label(__('Commercial Record'))
                    ->relationship('commercialRecord', 'entity_name')
                    ->required()
                    ->searchable(),
    
                Forms\Components\TextInput::make('license_name')
                    ->label(__('License Name'))
                    ->required()
                    ->maxLength(255),
    
                Forms\Components\TextInput::make('license_number')
                    ->label(__('License Number'))
                    ->required()
                    ->unique()
                    ->maxLength(50),
    
                Forms\Components\DatePicker::make('issue_date')
                    ->label(__('Issue Date'))
                    ->nullable(),
    
                Forms\Components\DatePicker::make('expiry_date')
                    ->label(__('Expiry Date (Gregorian)'))
                    ->nullable(),
    
                Forms\Components\DatePicker::make('expiry_date_hijri')
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
                    ->nullable()
                    ->maxLength(1000),
            ]);
    }
    

    public static function table(Table $table): Table
{
    return $table
        ->columns([
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
                ->dateTime()
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
