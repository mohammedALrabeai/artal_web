<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetResource\Pages;
use App\Models\Asset;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    // protected static ?string $navigationIcon = 'heroicon-o-collection';

    // نصوص الواجهة بالإنجليزية مباشرة

    protected static ?string $modelLabel = 'Asset';

    public static function getNavigationLabel(): string
    {
        return __('Assets');
    }

    public static function getPluralLabel(): string
    {
        return __('Assets');
    }
    public static function getNavigationGroup(): ?string
    {
        return __('Assets Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('asset_name')
                ->label(__('Asset Name'))
                ->placeholder('Enter asset name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label(__('Description'))
                ->placeholder('Provide a detailed description')
                ->rows(3)
                ->maxLength(65535),
            TextInput::make('serial_number')
                ->label(__('Serial Number'))
                ->placeholder('Serial Number')
                ->maxLength(255),
            DatePicker::make('purchase_date')
                ->label(__('Purchase Date'))
                ->placeholder('Select purchase date'),
            TextInput::make('value')
                ->label(__('Asset Value'))
                ->placeholder('Enter asset value')
                ->numeric(),
            TextInput::make('condition')
                ->label(__('Condition'))
                ->placeholder('e.g., New, Good, Needs Maintenance'),
            TextInput::make('status')
                ->label(__('Asset Status'))
                ->placeholder('e.g., Available, Assigned, Under Maintenance'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('asset_name')
                ->label(__('Asset Name'))
                ->sortable()
                ->searchable(),
            TextColumn::make('serial_number')
                ->label(__('Serial Number'))
                ->sortable()
                ->searchable(),
            TextColumn::make('purchase_date')
                ->label(__('Purchase Date'))
                ->date(),
            TextColumn::make('value')
                ->label(__('Asset Value')),
            TextColumn::make('condition')
                ->label(__('Condition')),
            TextColumn::make('status')
                ->label(__('Asset Status')),
        ])
            ->filters([
                // يمكن إضافة فلاتر حسب الحاجة
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label(__('Edit')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label(__('Delete')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // إضافة علاقات إذا دعت الحاجة (مثل عرض تعيينات العهد المرتبطة بهذا الأصل)
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}
