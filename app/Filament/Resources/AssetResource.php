<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Asset;
use Filament\Forms\Form;
use App\Enums\AssetStatus;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\AssetResource\Pages;

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
            Select::make('status')
    ->label(__('Status'))
    ->options(\App\Enums\AssetStatus::labels()) // نفس التسميات التي عرّفناها في enum
    ->default(AssetStatus::AVAILABLE->value)
    ->required(true),
        ]);
    }

    public static function table(Table $table): Table
    {

        $table->recordClasses(function ($record): string {
            if (! $record instanceof \App\Models\Asset) {
                return '';
            }
            return ($record->status?->value === \App\Enums\AssetStatus::CHARGED->value)
                ? 'bg-rose-50 dark:bg-rose-950/20'
                : '';
        });
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
            BadgeColumn::make('status')
                ->label(__('Status'))
                ->getStateUsing(function ($record) {
                    // قد يكون $record = null في بعض المراحل
                    if (! $record instanceof \App\Models\Asset) {
                        return AssetStatus::AVAILABLE->value;
                    }
                    return $record->status?->value ?? AssetStatus::AVAILABLE->value;
                })
                ->formatStateUsing(fn($state) => \App\Enums\AssetStatus::labels()[$state] ?? (string) $state)
                ->colors([
                    'success' => fn($state) => $state === AssetStatus::AVAILABLE->value,
                    'danger'  => fn($state) => $state === AssetStatus::CHARGED->value,
                    'warning' => fn($state) => in_array($state, [
                        AssetStatus::MAINTENANCE->value,
                        AssetStatus::DAMAGED->value,
                        AssetStatus::LOST->value,
                    ], true),
                    'gray'    => fn($state) => $state === AssetStatus::RETIRED->value,
                ])
                ->toggleable(),



            BadgeColumn::make('availability')
                ->label(__('Availability'))
                ->getStateUsing(fn($record) => $record->openAssignment ? 'Assigned' : 'Available')
                ->colors([
                    'success' => fn($state) => $state === 'Available',
                    'warning' => fn($state) => $state === 'Assigned',
                ]),
        ])
            //         ->recordClasses(function (Model $record): array {
            //     /** @var Asset $record */
            //     return [
            //         'bg-rose-50 dark:bg-rose-950/20' => $record instanceof Asset
            //             && $record->status?->value === \App\Enums\AssetStatus::CHARGED->value,
            //     ];
            // })
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
