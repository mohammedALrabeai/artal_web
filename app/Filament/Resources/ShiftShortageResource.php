<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftShortageResource\Pages;
use App\Filament\Resources\ShiftShortageResource\Widgets\ShiftEmployeeShortageOverview;
use App\Models\Attendance;
use App\Models\EmployeeProjectRecord;
// use Illuminate\Support\Facades\DB;
use App\Models\Shift;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\Builder;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShiftShortageResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'نقص الموظفين لكل وردية';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('zone.project.area.name')->label('المنطقة'),
                Tables\Columns\TextColumn::make('zone.project.name')->label('المشروع'),
                Tables\Columns\TextColumn::make('zone.name')->label('الموقع'),
                Tables\Columns\TextColumn::make('name')->label('الوردية'),
                Tables\Columns\TextColumn::make('emp_no')->label('الموظفين المطلوبين'),

                Tables\Columns\TextColumn::make('assigned_employees')
                    ->label('الموظفين الحاليين')
                    ->getStateUsing(fn ($record) => EmployeeProjectRecord::where('shift_id', $record->id)
                        ->where('status', 1) // جلب الموظفين النشطين فقط
                        ->count()
                    ),

                Tables\Columns\TextColumn::make('shortage')
                    ->label('النقص')
                    ->getStateUsing(fn ($record) => max(0, $record->emp_no - EmployeeProjectRecord::where('shift_id', $record->id)
                        ->where('status', 1) // جلب الموظفين النشطين فقط
                        ->count())
                    )
                    ->color(fn ($record) => ($record->emp_no - EmployeeProjectRecord::where('shift_id', $record->id)->where('status', 1)->count()) > 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn ($record) => ($record->emp_no - EmployeeProjectRecord::where('shift_id', $record->id)->where('status', 1)->count()) > 0
                        ? max(0, $record->emp_no - EmployeeProjectRecord::where('shift_id', $record->id)->where('status', 1)->count()).' ⛔'
                        : 'مكتمل ✅'
                    ),

                Tables\Columns\TextColumn::make('absent_employees')
                    ->label('عدد الغياب')
                    ->getStateUsing(fn ($record) => Attendance::where('shift_id', $record->id)
                        ->where('status', 'absent')
                        ->whereDate('date', today()) // ✅ تصفية على تاريخ اليوم فقط
                        ->count()
                    )
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} ⛔" : 'لا يوجد غياب ✅'),

                // ✅ **عمود عدد المغطيين في الموقع**
                Tables\Columns\TextColumn::make('coverage_employees')
                    ->label('عدد المغطيين')
                    ->getStateUsing(fn ($record) => Attendance::where('shift_id', $record->id)
                        ->where('status', 'coverage')
                        ->whereDate('date', today()) // ✅ تصفية على تاريخ اليوم فقط
                        ->count()
                    )
                    ->color('success')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} ✅" : 'لا يوجد تغطية'),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('zone_id')
                    ->label('الموقع')
                    ->relationship('zone', 'name'),

            ])
            ->searchable()
            ->paginated();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShiftShortages::route('/'),
            // 'create' => Pages\CreateShift::route('/create'),
            // 'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ShiftEmployeeShortageOverview::class,
        ];
    }
}
