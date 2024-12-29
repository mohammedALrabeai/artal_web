<?php
namespace App\Filament\Resources;

use App\Models\RequestType;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use App\Filament\Resources\RequestTypeResource\Pages;


class RequestTypeResource extends Resource
{
    protected static ?string $model = RequestType::class;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('key')
                ->label(__('Key'))
                ->required()
                ->unique(RequestType::class, 'key'),
            Forms\Components\TextInput::make('name')
                ->label(__('Name'))
                ->required(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')->label(__('Key')),
                Tables\Columns\TextColumn::make('name')->label(__('Name')),
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
