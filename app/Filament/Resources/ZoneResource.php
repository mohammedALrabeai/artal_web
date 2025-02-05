<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZoneResource\Pages;
use App\Models\Pattern;
use App\Models\Project;
use App\Models\Zone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ZoneResource extends Resource
{
    protected static ?string $model = Zone::class;

    protected static ?int $navigationSort = -9;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    protected static ?string $navigationIcon = 'heroicon-o-map-pin'; // أيقونة المورد

    public static function getNavigationLabel(): string
    {
        return __('Zones');
    }

    public static function getPluralLabel(): string
    {
        return __('Zones');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Zone & Project Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('Name'))
                ->required()
                ->maxLength(255),

            Forms\Components\DatePicker::make('start_date')
                ->label(__('Start Date'))
                ->required(),

            Forms\Components\Select::make('pattern_id')
                ->label(__('Pattern'))
                ->options(Pattern::all()->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('project_id')
                ->label(__('Project'))
                ->options(Project::all()->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('lat')
                ->label(__('Latitude'))
                ->required()
                ->default(fn ($record) => $record?->lat)
                ->id('lat'),

            Forms\Components\TextInput::make('longg')
                ->label(__('Longitude'))
                ->required()
                ->default(fn ($record) => $record?->longg)
                ->id('longg'),

            Forms\Components\TextInput::make('area')
                ->label(__('Range (meter)'))
                ->required()
                ->numeric(),

            Forms\Components\TextInput::make('emp_no')
                ->label(__('Number of Employees in one shift'))
                ->numeric()
                ->required(),
            Forms\Components\Toggle::make('status')
                ->label(__('Active'))
                ->default(true),
            Forms\Components\View::make('components.map-picker')
                ->label(__('Pick Location'))
                ->columnSpanFull(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pattern.name')
                    ->label(__('Pattern'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label(__('Project'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('Start Date'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('area')
                    ->label(__('Range (meter)')),

                Tables\Columns\TextColumn::make('emp_no')
                    ->label(__('Number of Employees')),

                Tables\Columns\BooleanColumn::make('status')
                    ->label(__('Active'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('pattern_id')
                    ->label(__('Pattern'))
                    ->options(Pattern::all()->pluck('name', 'id')),
                SelectFilter::make('project_id')
                    ->label(__('Project'))
                    ->options(Project::all()->pluck('name', 'id')),

                TernaryFilter::make('status')
                    ->label(__('Active'))
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZones::route('/'),
            'create' => Pages\CreateZone::route('/create'),
            'edit' => Pages\EditZone::route('/{record}/edit'),
        ];
    }
}
