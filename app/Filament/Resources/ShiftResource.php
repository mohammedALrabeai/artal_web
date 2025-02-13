<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Zone;
use Filament\Tables;
use App\Models\Shift;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Resources\ShiftResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;
    protected static ?int $navigationSort = -8; 

    protected static ?string $navigationIcon = 'heroicon-o-clock';

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
        return __('Shifts');
    }

    public static function getPluralLabel(): string
    {
        return __('Shifts');
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
                ->required(),
    
            Forms\Components\Select::make('zone_id')
                ->label(__('Zone'))
                ->options(fn () => Zone::pluck('name', 'id')->toArray())
                ->searchable()
                ->required(),
    
            Forms\Components\Select::make('type')
                ->label(__('Type'))
                ->options([
                    'morning' => __('Morning'),
                    'evening' => __('Evening'),
                    'morning_evening' => __('Morning-Evening'),
                    'evening_morning' => __('Evening-Morning'),
                ])
                ->required(),
    
            Forms\Components\TimePicker::make('morning_start')
                ->label(__('Morning Start')),
    
            Forms\Components\TimePicker::make('morning_end')
                ->label(__('Morning End')),
    
            Forms\Components\TimePicker::make('evening_start')
                ->label(__('Evening Start')),
    
            Forms\Components\TimePicker::make('evening_end')
                ->label(__('Evening End')),
    
            Forms\Components\TextInput::make('early_entry_time')
                ->label(__('Early Entry Time (Minutes)'))
                ->numeric()
                ->required(),
    
            Forms\Components\TextInput::make('last_entry_time')
                ->label(__('Last Entry Time (Minutes)'))
                ->numeric()
                ->required(),
    
            Forms\Components\TextInput::make('early_exit_time')
                ->label(__('Early Exit Time (Minutes)'))
                ->numeric()
                ->required(),
    
            Forms\Components\TextInput::make('last_time_out')
                ->label(__('Last Time Out (Minutes)'))
                ->numeric()
                ->required(),
    
            Forms\Components\DatePicker::make('start_date')
                ->label(__('Start Date'))
                ->required(),
    
            Forms\Components\TextInput::make('emp_no')
                ->label(__('Number of Employees'))
                ->numeric()
                ->required(),
    
            Forms\Components\Toggle::make('status')
                ->label(__('Active'))
                ->default(true),
        ]);
    }
    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('zone.name')
                    ->label(__('Zone'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('morning_start')
                    ->label(__('Morning Start'))
                    ->time(),

                Tables\Columns\TextColumn::make('evening_start')
                    ->label(__('Evening Start'))
                    ->time(),

                Tables\Columns\BooleanColumn::make('status')
                    ->label(__('Active'))
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('last_entry_time')
                    ->label(__('Last Entry Time (Minutes)'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('early_exit_time')
                    ->label(__('Early Exit Time (Minutes)'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_time_out')
                    ->label(__('Last Time Out (Minutes)'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('Start Date'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('emp_no')
                    ->label(__('Number of Employees'))
                    ->sortable(),

            ])
            ->filters([
                SelectFilter::make('zone_id')
                    ->label(__('Zone'))
                    ->options(fn () => Zone::pluck('name', 'id')->toArray()),

                SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options([
                        'morning' => __('Morning'),
                        'evening' => __('Evening'),
                        'morning_evening' => __('Morning-Evening'),
                        'evening_morning' => __('Evening-Morning'),
                    ]),

                TernaryFilter::make('status')
                    ->label(__('Active'))
                    ->nullable(),
            ])
            ->paginationPageOptions([10, 25, 50, 100])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make()
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }
}
