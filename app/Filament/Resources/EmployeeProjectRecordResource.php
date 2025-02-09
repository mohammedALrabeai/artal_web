<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeProjectRecordResource\Pages;
use App\Forms\Components\EmployeeSelect;
use App\Models\Employee;
use App\Models\EmployeeProjectRecord;
use App\Models\Project;
use App\Models\Zone;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class EmployeeProjectRecordResource extends Resource
{
    protected static ?string $model = EmployeeProjectRecord::class;

    // navigation icon
    protected static ?string $navigationIcon = 'fluentui-globe-person-20-o';

    public static function getNavigationBadge(): ?string
    {
        // ✅ إخفاء العدد عن المستخدمين غير الإداريين
        if (! auth()->user()?->hasRole('admin')) {
            return null;
        }

        return static::getModel()::count();
    }

    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('Employee Project Records');
    }

    public static function getPluralLabel(): string
    {
        return __('Employee Project Records');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Employee Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            EmployeeSelect::make(),

            // Select::make('employee_id')
            // ->label(__('Employee'))
            // ->searchable()
            // ->getSearchResultsUsing(function (string $search) {
            //     return \App\Models\Employee::query()
            //         ->where('national_id', 'like', "%{$search}%") // البحث باستخدام رقم الهوية
            //         ->orWhere('first_name', 'like', "%{$search}%") // أو البحث باستخدام الاسم
            //         ->limit(50)
            //         ->pluck('first_name', 'id'); // استرجاع الاسم فقط
            // })
            // ->getOptionLabelUsing(function ($value) {
            //     $employee = \App\Models\Employee::find($value);
            //     return $employee ? "{$employee->first_name} {$employee->family_name}" : null; // عرض الاسم فقط
            // })
            // ->required(),

            Select::make('project_id')
                ->label(__('Project'))
                ->options(\App\Models\Project::all()->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->reactive()
                ->afterStateUpdated(function (callable $set) {
                    $set('zone_id', null); // إعادة تعيين اختيار الموقع عند تغيير المشروع
                    $set('shift_id', null); // إعادة تعيين اختيار الوردية عند تغيير المشروع
                }),

            // اختيار الموقع
            Select::make('zone_id')
                ->label(__('Zone'))
                ->options(function (callable $get) {
                    $projectId = $get('project_id');
                    if (! $projectId) {
                        return [];
                    }

                    return \App\Models\Zone::where('project_id', $projectId)->pluck('name', 'id');
                })
                ->searchable()
                ->required()
                ->reactive()
                ->afterStateUpdated(function (callable $set) {
                    $set('shift_id', null); // إعادة تعيين اختيار الوردية عند تغيير الموقع
                }),

            // اختيار الوردية
            Select::make('shift_id')
                ->label(__('Shift'))
                ->options(function (callable $get) {
                    $zoneId = $get('zone_id');
                    if (! $zoneId) {
                        return [];
                    }

                    return \App\Models\Shift::where('zone_id', $zoneId)->pluck('name', 'id');
                })
                ->searchable()
                ->required(),

            DatePicker::make('start_date')
                ->label(__('Start Date'))
                ->required(),

            DatePicker::make('end_date')
                ->label(__('End Date')),

            Forms\Components\Toggle::make('status')
                ->label(__('Status'))
                ->onColor('success') // لون عند التفعيل
                ->offColor('danger') // لون عند الإيقاف
                ->required(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('employee.first_name')
                //     ->label(__('Employee'))
                //     ->sortable()
                //     ->searchable(),
                TextColumn::make('full_name')
                    ->label(__('Employee'))
                    ->getStateUsing(fn ($record) => $record->employee->first_name.' '.
                        $record->employee->father_name.' '.
                        $record->employee->grandfather_name.' '.
                        $record->employee->family_name
                    )
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('employee', function ($subQuery) use ($search) {
                            $subQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('father_name', 'like', "%{$search}%")
                                ->orWhere('grandfather_name', 'like', "%{$search}%")
                                ->orWhere('family_name', 'like', "%{$search}%")
                                ->orWhere('national_id', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                TextColumn::make('employee.national_id')
                    ->label(__('National ID'))
                    ->searchable(),

                TextColumn::make('project.name')
                    ->label(__('Project'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('zone.name')
                    ->label(__('Zone'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('shift.name')
                    ->label(__('Shift'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('start_date')
                    ->label(__('Start Date'))
                    ->date(),

                TextColumn::make('end_date')
                    ->label(__('End Date'))
                    ->date(),
                BooleanColumn::make('status')
                    ->label(__('Status'))
                    ->sortable(),
                TextColumn::make('work_pattern')
                    ->label('نمط العمل')
                    ->getStateUsing(fn ($record) => self::calculateWorkPattern($record))
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label(__('Project'))
                    ->options(Project::all()->pluck('name', 'id')),

                SelectFilter::make('zone_id')
                    ->label(__('Zone'))
                    ->options(Zone::all()->pluck('name', 'id')),

                SelectFilter::make('employee_id')
                    ->label(__('Employee'))
                    ->options(Employee::all()->pluck('first_name', 'id')),

                TernaryFilter::make('status')
                    ->label(__('Status'))
                    ->nullable(),
            ])
            ->actions([
                Action::make('print')
                    ->label(__('Print Contract'))
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('employee_project_record.pdf', $record)) // إعادة توجيه إلى رابط PDF
                    ->color('primary'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->paginationPageOptions([10, 25, 50, 100])
            ->bulkActions([
                DeleteBulkAction::make(),
                ExportBulkAction::make(),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['status']) && is_bool($data['status'])) {
            $data['status'] = $data['status'] ? 'active' : 'completed';
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeProjectRecords::route('/'),
            'create' => Pages\CreateEmployeeProjectRecord::route('/create'),
            'edit' => Pages\EditEmployeeProjectRecord::route('/{record}/edit'),
        ];
    }

    private static function calculateWorkPattern($record)
    {
        $pattern = $record->shift->zone->pattern ?? null;

        if (! $pattern) {
            return '<span style="color: red;">❌ لا يوجد نمط محدد</span>';
        }

        $workingDays = $pattern->working_days;
        $offDays = $pattern->off_days;
        $cycleLength = $workingDays + $offDays;

        $startDate = Carbon::parse($record->start_date);
        $currentDate = Carbon::now();
        $totalDays = $currentDate->diffInDays($startDate);
        $currentDayInCycle = $totalDays % $cycleLength;

        $cycleNumber = (int) floor($totalDays / $cycleLength) + 1; // حساب رقم الدورة الحالية

        $daysView = [];

        for ($i = 0; $i < 30; $i++) {
            $dayInCycle = ($currentDayInCycle + $i) % $cycleLength;
            $isWorkDay = $dayInCycle < $workingDays;
            $date = $currentDate->copy()->addDays($i)->format('d M');

            $color = $isWorkDay ? 'green' : 'red';
            $label = $isWorkDay ? '' : '';

            // ✅ إضافة "صباحًا" أو "مساءً" بجانب أيام العمل
            if ($isWorkDay) {
                $shiftType = ($cycleNumber % 2 == 1) ? 'ص' : 'م';
                $label .= " - $shiftType";
            }

            // $daysView[] = "<span style='padding: 4px; border-radius: 5px; background-color: $color; color: white; margin-right: 5px;'>$date: $label</span>";
            $daysView[] = "
            <span style='
                padding: 4px; 
                border-radius: 5px; 
                background-color: $color; 
                color: white; 
                display: inline-block; 
                width: 110px; /* ضمان نفس العرض */
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
