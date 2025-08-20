<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftShortageResource\Pages;
use App\Filament\Resources\ShiftShortageResource\Widgets\ShiftEmployeeShortageOverview;
use App\Models\Attendance;
use App\Models\EmployeeProjectRecord;
use App\Models\Shift;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

class ShiftShortageResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
  
    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'نقص الموظفين لكل وردية';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('zone.project.area.name')->label('المنطقة')->searchable(),
                Tables\Columns\TextColumn::make('zone.project.name')->label('المشروع')->searchable(),
                Tables\Columns\TextColumn::make('zone.name')->label('الموقع')->searchable(),
                Tables\Columns\TextColumn::make('name')->label('الوردية')->searchable(),
                Tables\Columns\TextColumn::make('emp_no')->label('الموظفين المطلوبين'),
                Tables\Columns\TextColumn::make('assigned_employees')
                    ->label('الموظفين الحاليين')
                    ->getStateUsing(fn ($record) => EmployeeProjectRecord::where('shift_id', $record->id)
                        ->where('status', 1)
                        ->count()
                    ),

                     Tables\Columns\TextColumn::make('shortage')
                    ->label('النقص')
                    ->getStateUsing(fn ($record) => max(0, $record->emp_no - EmployeeProjectRecord::where('shift_id', $record->id)
                        ->where('status', 1)
                        ->count())
                    )
                    ->color(fn ($record) => ($record->emp_no - EmployeeProjectRecord::where('shift_id', $record->id)->where('status', 1)->count()) > 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn ($record) => ($record->emp_no - EmployeeProjectRecord::where('shift_id', $record->id)->where('status', 1)->count()) > 0
                        ? max(0, $record->emp_no - EmployeeProjectRecord::where('shift_id', $record->id)->where('status', 1)->count()).' ⛔'
                        : 'مكتمل ✅'
                    ),
             



                Tables\Columns\TextColumn::make('shortage_days_count')
                    ->label('أيام النقص الحالية')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} يوم " : 'لا يوجد نقص ✅'),

                Tables\Columns\TextColumn::make('absent_employees')
                    ->label('عدد الغياب')
                    ->getStateUsing(fn ($record) => Attendance::where('shift_id', $record->id)
                        ->where('status', 'absent')
                        ->whereDate('date', today())
                        ->count()
                    )
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} ⛔" : 'لا يوجد غياب ✅'),
                Tables\Columns\TextColumn::make('coverage_employees')
                    ->label('عدد المغطيين')
                    ->getStateUsing(fn ($record) => Attendance::where('shift_id', $record->id)
                        ->where('status', 'coverage')
                        ->whereDate('date', today())
                        ->count()
                    )
                    ->color('success')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} ✅" : 'لا يوجد تغطية'),

                Tables\Columns\TextColumn::make('status_summary')
                    ->label('حالة الربط')
                    ->getStateUsing(function ($record) {
                        $shiftStatus = $record->status ? '✅ وردية نشطة' : '❌ وردية معطلة';
                        $zoneStatus = optional($record->zone)->status ? '✅ موقع نشط' : '❌ موقع معطل';
                        $projectStatus = optional($record->zone?->project)->status ? '✅ مشروع نشط' : '❌ مشروع معطل';

                        return "{$shiftStatus} | {$zoneStatus} | {$projectStatus}";
                    })
                    ->badge()
                    ->color(function ($record) {
                        $isAllActive = $record->status
                            && optional($record->zone)->status
                            && optional($record->zone?->project)->status;

                        return $isAllActive ? 'success' : 'danger';
                    })
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                    Tables\Columns\TextColumn::make('work_pattern')
    ->label('نمط العمل (30 يوم)')
    ->html() // لعرض HTML
    ->toggleable(isToggledHiddenByDefault: false) // غيّرها لو تحب يكون مخفي افتراضيًا
    ->getStateUsing(fn (Shift $record) => self::calculateShiftWorkPattern($record)),


            ])
            ->filters([
                // فلتر لحالة المشروع (نشط / معطل / الكل)
                SelectFilter::make('project_status')
                    ->label('حالة المشروع')
                    ->default('active')
                    ->options([
                        'active' => 'المشاريع النشطة',
                        'inactive' => 'المشاريع المعطلة',
                        'all' => 'الكل',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'active') {
                            $query->whereHas('zone.project', function ($q) {
                                $q->where('status', true);
                            });
                        } elseif ($data['value'] === 'inactive') {
                            $query->whereHas('zone.project', function ($q) {
                                $q->where('status', false);
                            });
                        }
                        // عند اختيار 'all' لا يتم تطبيق أي شرط
                    }),
                SelectFilter::make('shortage_filter')
                    ->label('عرض الورديات')
                    ->options([
                        'with_shortage' => 'مع النقص فقط',
                        'all' => 'جميع الورديات',
                    ])
                    ->default('with_shortage')
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'with_shortage') {
                            $query->whereRaw('emp_no > (
                                SELECT COUNT(*)
                                FROM employee_project_records
                                WHERE employee_project_records.shift_id = shifts.id
                                AND employee_project_records.status = 1
                            )');
                        }
                        // عند اختيار 'all'، لا يتم تطبيق أي شرط إضافي
                    }),
                Tables\Filters\SelectFilter::make('zone_id')
                    ->label('الموقع')
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->relationship('zone', 'name'),
            ])
            ->headerActions([
                // ExportAction::make()
                //     ->label('تصدير إلى Excel')
                //     ->fileName('ShiftShortagesExport') // فقط هذا مدعوم
                //     ->only([
                //         'zone.project.area.name',
                //         'zone.project.name',
                //         'zone.name',
                //         'name',
                //         'emp_no',
                //         'assigned_employees',
                //         'shortage',
                //         'absent_employees',
                //         'coverage_employees',
                //     ]),

            ])
            ->paginated();
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()->whereHas('zone.project', function ($query) {
    //         $query->where('status', true); // استخدم true أو 1 حسب نوع البيانات في العمود
    //     });
    // }

   public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with(['zone.pattern', 'zone.project.area']) // ✅ مهم للعمود الجديد
        ->where('status', 1) // حالة الوردية نفسها
        ->whereHas('zone', function ($zoneQuery) {
            $zoneQuery->where('status', 1)
                ->whereHas('project', function ($projectQuery) {
                    $projectQuery->where('status', 1);
                });
        });
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShiftShortages::route('/'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ShiftEmployeeShortageOverview::class,
        ];
    }


    private static function calculateShiftWorkPattern(Shift $shift, int $days = 30): string
{
    // تحقق من وجود النمط
    $pattern = $shift->zone->pattern ?? null;
    if (! $pattern) {
        return '<span style="color: red; font-weight: bold; padding: 4px; display: inline-block; width: 100px; text-align: center;">❌ غير متوفر</span>';
    }

    $workingDays = (int) $pattern->working_days;
    $offDays     = (int) $pattern->off_days;
    $cycleLength = max(1, $workingDays + $offDays); // أمان

    // نبدأ حساب الدورة من تاريخ بداية الوردية
    $shiftStart = \Illuminate\Support\Carbon::parse($shift->start_date);
    $today      = \Illuminate\Support\Carbon::now('Asia/Riyadh');

    $out = [];

    for ($i = 0; $i < $days; $i++) {
        $dateObj     = $today->copy()->addDays($i);
        $displayDate = $dateObj->format('d M');

        // قبل بداية الوردية: نعرضها كغير سارية
        if ($dateObj->lt($shiftStart)) {
            $out[] = "
            <span style='
                padding: 4px;
                border-radius: 5px;
                background-color: #9E9E9E;
                color: white;
                display: inline-block;
                width: 110px;
                height: 30px;
                margin-bottom: 0px;
                text-align: center;
                margin-right: 5px;
                font-weight: bold;
            '>
                $displayDate - 🕒
            </span>";
            continue;
        }

        // حساب موقع اليوم داخل الدورة
        $totalDaysSinceStart = $shiftStart->diffInDays($dateObj);
        $dayInCycle          = $totalDaysSinceStart % $cycleLength;
        $cycleNumber         = (int) floor($totalDaysSinceStart / $cycleLength) + 1;

        $isWorkDay = $dayInCycle < $workingDays;

        // لون اليوم: أخضر عمل / أحمر إجازة
        $color = $isWorkDay ? 'green' : 'red';
        $label = '';

        // تحديد صباح/مساء لأيام العمل فقط وفق نوع الوردية
        if ($isWorkDay) {
            // افتراضيًا تناوب حسب رقم الدورة
            $shiftType = ($cycleNumber % 2 === 1) ? 'ص' : 'م';

            // احترام نوع الوردية إن كان محددًا
            switch ($shift->type) {
                case 'morning':
                    $shiftType = 'ص';
                    break;
                case 'evening':
                    $shiftType = 'م';
                    break;
                case 'morning_evening':
                    // اترك الافتراضي (أو ثبّت 'ص' لو تفضّل)
                    break;
                case 'evening_morning':
                    $shiftType = ($cycleNumber % 2 === 1) ? 'م' : 'ص';
                    break;
            }

            $label = " - {$shiftType}";
        }

        $out[] = "
        <span style='
            padding: 4px;
            border-radius: 5px;
            background-color: {$color};
            color: white;
            display: inline-block;
            width: 110px;
            height: 30px;
            margin-bottom: 0px;
            text-align: center;
            margin-right: 5px;
            font-weight: bold;
        '>
            {$displayDate}{$label}
        </span>";
    }

    return implode(' ', $out);
}

}
