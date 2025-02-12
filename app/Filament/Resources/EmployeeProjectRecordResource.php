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
                TextColumn::make('previous_month_attendance')
                    ->label('دوام الشهر الماضي')
                    ->getStateUsing(fn ($record) => self::getPreviousMonthAttendance($record))
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    // private static function calculateWorkPattern($record)
    // {
    //     $pattern = $record->shift->zone->pattern ?? null;

    //     if (! $pattern) {
    //         return '<span style="color: red;">❌ لا يوجد نمط محدد</span>';
    //     }

    //     $workingDays = $pattern->working_days;
    //     $offDays = $pattern->off_days;
    //     $cycleLength = $workingDays + $offDays;

    //     $startDate = Carbon::parse($record->start_date);
    //     $currentDate = Carbon::now('Asia/Riyadh');
    //     $totalDays = $currentDate->diffInDays($startDate);
    //     $currentDayInCycle = $totalDays % $cycleLength;

    //     $cycleNumber = (int) floor($totalDays / $cycleLength) + 1; // حساب رقم الدورة الحالية

    //     $daysView = [];

    //     for ($i = 0; $i < 30; $i++) {
    //         $dayInCycle = ($currentDayInCycle + $i) % $cycleLength;
    //         $isWorkDay = $dayInCycle < $workingDays;
    //         $date = $currentDate->copy()->addDays($i)->format('d M');

    //         $color = $isWorkDay ? 'green' : 'red';
    //         $label = $isWorkDay ? '' : '';

    //         // ✅ إضافة "صباحًا" أو "مساءً" بجانب أيام العمل
    //         if ($isWorkDay) {
    //             $shiftType = ($cycleNumber % 2 == 1) ? 'ص' : 'م';
    //             $label .= " - $shiftType";
    //         }

    //         // $daysView[] = "<span style='padding: 4px; border-radius: 5px; background-color: $color; color: white; margin-right: 5px;'>$date: $label</span>";
    //         $daysView[] = "
    //         <span style='
    //             padding: 4px;
    //             border-radius: 5px;
    //             background-color: $color;
    //             color: white;
    //             display: inline-block;
    //             width: 110px; /* ضمان نفس العرض */
    //              height: 30px;
    //              margin-bottom: 0px; /* تقليل الهوامش بين الصفوف */

    //             text-align: center;
    //             margin-right: 5px;
    //             font-weight: bold;
    //         '>
    //             $date$label
    //         </span>";
    //     }

    //     return implode(' ', $daysView);
    // }
    private static function calculateWorkPattern($record)
    {
        if (! $record->shift || ! $record->shift->zone || ! $record->shift->zone->pattern) {
            return '<span style="color: red; font-weight: bold; padding: 4px; display: inline-block; width: 100px; text-align: center;">❌ غير متوفر</span>';
        }

        $pattern = $record->shift->zone->pattern;
        $workingDays = (int) $pattern->working_days;
        $offDays = (int) $pattern->off_days;
        $cycleLength = $workingDays + $offDays;

        // ✅ حساب بداية الدورة من `shift.start_date`
        $startDate = Carbon::parse($record->shift->start_date);
        $currentDate = Carbon::now('Asia/Riyadh');

        $daysView = [];

        for ($i = 0; $i < 30; $i++) {
            $targetDate = $currentDate->copy()->addDays($i); // ✅ تحديد تاريخ الخلية
            $totalDays = $startDate->diffInDays($targetDate); // ✅ حساب الفرق من بداية الوردية وليس من اليوم الحالي

            // ✅ حساب اليوم داخل الدورة بناءً على `totalDays`
            $currentDayInCycle = $totalDays % $cycleLength;
            $cycleNumber = (int) floor($totalDays / $cycleLength) + 1; // ✅ حساب الدورة الزمنية الصحيحة

            // ✅ تحديد إذا كان اليوم "عمل" أو "إجازة" بناءً على `workingDays`
            $isWorkDay = $currentDayInCycle < $workingDays;
            $date = $targetDate->format('d M');

            $color = $isWorkDay ? 'green' : 'red';
            $label = $isWorkDay ? '' : '';

            // ✅ تحديد الفترة "صباحًا" أو "مساءً" فقط إذا كان يوم عمل
            if ($isWorkDay) {
                $shiftType = ($cycleNumber % 2 == 1) ? 'ص' : 'م';
                $label .= " - $shiftType";
            }

            // ✅ تحسين التنسيق وتقليل الهوامش بين العناصر
            $daysView[] = "
             <span style='
                padding: 4px;
                border-radius: 5px;
                background-color: $color;
                color: white;
                display: inline-block;
                width: 110px; /* ضمان نفس العرض */
                 height: 30px;
                 margin-bottom: 0px; /* تقليل الهوامش بين الصفوف */

                text-align: center;
                margin-right: 5px;
                font-weight: bold;
            '>
                $date$label
            </span>";
        }

        return implode(' ', $daysView);
    }

    private static function getPreviousMonthAttendance($record)
    {
        $employeeId = $record->employee_id;
        $currentDate = Carbon::now('Asia/Riyadh');
        $startDate = $currentDate->copy()->subDays(30)->format('Y-m-d');
        $endDate = $currentDate->format('Y-m-d');

        // جلب بيانات الحضور للموظف خلال آخر 30 يومًا
        $attendances = \App\Models\Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy('date'); // تحويل النتيجة إلى مصفوفة تعتمد على التاريخ

        // خريطة الألوان الجديدة لكل حالة
        $attendanceColors = [
            'present' => '#2E7D32',  // أخضر غامق
            'absent' => '#D32F2F',   // أحمر غامق
            'coverage' => '#F9A825', // أصفر برتقالي
            'M' => '#E91E63',        // وردي غامق
            'leave' => '#388E3C',    // أخضر غامق
            'UV' => '#F57C00',       // برتقالي غامق
            'W' => '#795548',        // بني غامق
            'N/A' => '#9E9E9E',      // رمادي غامق
        ];

        $daysView = [];

        for ($i = 30; $i >= 1; $i--) {
            $date = $currentDate->copy()->subDays($i)->format('Y-m-d');
            $displayDate = $currentDate->copy()->subDays($i)->format('d M');

            $attendance = $attendances[$date] ?? null;
            $status = $attendance ? $attendance->status : 'N/A';
            $color = $attendanceColors[$status] ?? '#9E9E9E'; // إذا لم يكن هناك لون، استخدم الرمادي

            $daysView[] = "
            <span style='
                padding: 6px; 
                border-radius: 5px; 
                background-color: $color; 
                color: white; 
                display: inline-block; 
                width: 120px; /* ضمان نفس العرض */
                height: 30px;
                text-align: center; 
                border: 1px solid black; /* إضافة حد أسود */
                margin-right: 5px; 
                font-weight: bold;
            '>
                $displayDate - $status
            </span>";
        }

        return implode(' ', $daysView);
    }
}
