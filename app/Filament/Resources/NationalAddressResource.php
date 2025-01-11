<?php

namespace App\Filament\Resources;

use App\Models\NationalAddress;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\NationalAddressResource\Pages;

class NationalAddressResource extends Resource
{
    protected static ?string $model = NationalAddress::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return __('National Addresses');
    }

    public static function getPluralLabel(): string
    {
        return __('National Addresses');
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
                    ->preload()
                    ->searchable(),

                Forms\Components\DatePicker::make('expiry_date')
                    ->label(__('Expiry Date'))
                    ->nullable(),

                Forms\Components\Textarea::make('notes')
                    ->label(__('Notes'))
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

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label(__('Expiry Date'))
                    ->dateTime()
                    ->sortable(),

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
            'index' => Pages\ListNationalAddresses::route('/'),
            'create' => Pages\CreateNationalAddress::route('/create'),
            'edit' => Pages\EditNationalAddress::route('/{record}/edit'),
        ];
    }
}
