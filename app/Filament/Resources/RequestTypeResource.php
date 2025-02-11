<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestTypeResource\Pages;
use App\Models\RequestType;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class RequestTypeResource extends Resource
{
    protected static ?string $model = RequestType::class;

    public static function getNavigationLabel(): string
    {
        return __('Request Types');
    }

    public static function getPluralLabel(): string
    {
        return __('Request Types');
    }

    public static function getLabel(): string
    {
        return __('Request Type');
    }

    public static function getSingularLabel(): string
    {
        return __('Request Type');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Request Management');
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('key')
                ->label(__('Key'))
                ->required()
                ->unique(RequestType::class, 'key', ignoreRecord: true),
            Forms\Components\TextInput::make('name')
                ->label(__('Name'))
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label(__('Can be used?'))
                ->onColor('success')
                ->offColor('danger')
                ->default(true) // ✅ القيم الافتراضية "نعم"
                ->required(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')->label(__('Key')),
                Tables\Columns\TextColumn::make('name')->label(__('Name')),
                Tables\Columns\IconColumn::make('is_active')->label(__('Can be used?'))->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequestTypes::route('/'),
            'create' => Pages\CreateRequestType::route('/create'),
            'edit' => Pages\EditRequestType::route('/{record}/edit'),
        ];
    }
}
