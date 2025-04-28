<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Models\Shift;
use App\Models\Zone;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?int $navigationSort = -8;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function getNavigationBadge(): ?string
    {
        // ✅ إخفاء العدد عن المستخدمين غير الإداريين
        if (! auth()->user()?->hasRole('admin')) {
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
                    ->searchable()
                    ->copyable()
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('zone.name')
                    ->label(__('Zone'))
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessageDuration(1500),

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
                Tables\Columns\TextColumn::make('work_pattern') // 🆕 إضافة نمط العمل
                    ->label('نمط العمل')
                    ->getStateUsing(fn ($record) => self::calculateWorkPattern($record))
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                ExportBulkAction::make(),
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

    private static function calculateWorkPattern($record)
    {
        if (! $record->zone || ! $record->zone->pattern) {
            return '<span style="color: red; font-weight: bold; padding: 4px; display: inline-block; width: 100px; text-align: center;">❌ غير متوفر</span>';
        }

        $pattern = $record->zone->pattern;
        $workingDays = (int) $pattern->working_days;
        $offDays = (int) $pattern->off_days;
        $cycleLength = $workingDays + $offDays;

        $startDate = Carbon::parse($record->start_date);
        $currentDate = Carbon::now('Asia/Riyadh');

        $daysView = [];

        for ($i = 0; $i < 30; $i++) {
            $targetDate = $currentDate->copy()->addDays($i);
            $totalDays = $startDate->diffInDays($targetDate);
            $currentDayInCycle = $totalDays % $cycleLength;
            $cycleNumber = (int) floor($totalDays / $cycleLength) + 1;

            $isWorkDay = $currentDayInCycle < $workingDays;
            $date = $targetDate->format('d M');

            $color = $isWorkDay ? 'green' : 'red';
            $label = $isWorkDay ? '' : '';

            if ($isWorkDay) {
                $shiftType = ($cycleNumber % 2 == 1) ? 'ص' : 'م';
                switch ($record->type) {
                    case 'morning':
                        $shiftType = 'ص';
                        break;

                    case 'evening':
                        $shiftType = 'م';
                        break;

                    case 'morning_evening':
                        break;

                    case 'evening_morning':
                        $shiftType = ($cycleNumber % 2 == 1) ? 'م' : 'ص';
                        break;
                }
                $label .= " - $shiftType";
            }

            $daysView[] = "
         <span style='
            padding: 4px;
            border-radius: 5px;
            background-color: $color;
            color: white;
            display: inline-block;
            width: 110px;
            height: 30px;
            margin-bottom: 0px;
            text-align: center;
            margin-right: 5px;
            font-weight: bold;
        '>
            $date$label
        </span>";
        }

        return implode(' ', $daysView);
    }
}
