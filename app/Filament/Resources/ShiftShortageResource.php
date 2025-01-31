<?php
namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Shift;
use Filament\Tables\Table;
use Filament\Resources\Resource;
// use Illuminate\Support\Facades\DB;
use App\Models\EmployeeProjectRecord;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\Builder;

use App\Filament\Resources\ShiftShortageResource\Pages;
use App\Filament\Resources\ShiftShortageResource\Widgets\ShiftEmployeeShortageOverview;



class ShiftShortageResource extends Resource
{
    protected static ?string $model = Shift::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'نقص الموظفين لكل وردية';

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            Tables\Columns\TextColumn::make('zone.name')->label('الموقع'),
            Tables\Columns\TextColumn::make('name')->label('الوردية'),
            Tables\Columns\TextColumn::make('emp_no')->label('الموظفين المطلوبين'),

            Tables\Columns\TextColumn::make('assigned_employees')
                ->label('الموظفين الحاليين')
                ->getStateUsing(fn ($record) => 
                    EmployeeProjectRecord::where('shift_id', $record->id)
                        ->where('status', 1) // جلب الموظفين النشطين فقط
                        ->count()
                ),

            Tables\Columns\TextColumn::make('shortage')
                ->label('النقص')
                ->getStateUsing(fn ($record) => 
                    max(0, $record->emp_no - EmployeeProjectRecord::where('shift_id', $record->id)
                        ->where('status', 1) // جلب الموظفين النشطين فقط
                        ->count())
                )
                ->color(fn ($record) => ($record->emp_no - EmployeeProjectRecord::where('shift_id', $record->id)->where('status', 1)->count()) > 0 ? 'danger' : 'success')
                ->formatStateUsing(fn ($record) => 
                    ($record->emp_no - EmployeeProjectRecord::where('shift_id', $record->id)->where('status', 1)->count()) > 0 
                    ? max(0, $record->emp_no - EmployeeProjectRecord::where('shift_id', $record->id)->where('status', 1)->count()) . " ⛔"
                    : "مكتمل ✅"
                ),
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
