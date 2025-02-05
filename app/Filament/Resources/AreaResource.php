<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Area;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\AreaResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AreaResource\RelationManagers;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;
    protected static ?int $navigationSort = -11; 

    protected static ?string $navigationIcon = 'heroicon-o-map';

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
        return __('Areas');
    }
    
    public static function getPluralLabel(): string
    {
        return __('Areas');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('Zone & Project Management');
    }
    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label(__('Name'))
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                ->label(__('Description'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                ->label(__('Name'))
                    ->searchable(),
                    Tables\Columns\TextColumn::make('description')->label(__('Description'))->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                ExportBulkAction::make()
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
            'index' => Pages\ListAreas::route('/'),
            'create' => Pages\CreateArea::route('/create'),
            'edit' => Pages\EditArea::route('/{record}/edit'),
        ];
    }
}
