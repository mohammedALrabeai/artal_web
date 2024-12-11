<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Attendance;
use App\Models\Shift;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}

   public static function getNavigationLabel(): string
{
    return __('Attendances');
}

public static function getPluralLabel(): string
{
    return __('Attendances');
}

public static function getNavigationGroup(): ?string
{
    return __('Employee Management');
}



    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('employee_id')
                ->label(__('Employee'))
                ->options(\App\Models\Employee::all()->pluck('first_name', 'id'))
                ->searchable()
                ->required(),
    
            Forms\Components\DatePicker::make('date')
                ->label(__('Date'))
                ->required(),
    
                // Forms\Components\Select::make('zone_id')
                // ->label(__('Zone'))
                // ->options(\App\Models\Zone::all()->pluck('name', 'id'))
                // ->searchable()
                // ->required(),
                // Forms\Components\Select::make('shift_id')
                // ->label(__('Shift'))
                // ->relationship('shift', 'name')
                // ->required(),
                
                 // اختيار الموقع
        Select::make('zone_id')
        ->label(__('Zone'))
         ->options(\App\Models\Zone::all()->pluck('name', 'id'))

        // ->options(function (callable $get) {
        //     $projectId = $get('project_id');
        //     if (!$projectId) {
        //         return [];
        //     }
        //     return \App\Models\Zone::where('project_id', $projectId)->pluck('name', 'id');
        // })
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
            if (!$zoneId) {
                return [];
            }
            return \App\Models\Shift::where('zone_id', $zoneId)->pluck('name', 'id');
        })
        ->searchable()
        ->required(),
        Forms\Components\Select::make('ismorning')
        ->label(__('Time of Day')) // يمكن تغيير النص حسب الحاجة
        ->options([
            true => __('Morning'),  // صباحًا
            false => __('Evening'), // مساءً
        ])
        ->nullable() // للسماح بالقيمة الافتراضية null
        ->default(null) // إذا كنت تريد قيمة افتراضية
        ->required(),
    
            Forms\Components\TimePicker::make('check_in')
                ->label(__('Check In')),
            Forms\Components\DateTimePicker::make('check_in_datetime')
                ->label(__('Check In Datetime'))
                ->required(false)
            ,
            Forms\Components\TimePicker::make('check_out')
                ->label(__('Check Out')),
            Forms\Components\DateTimePicker::make('check_out_datetime')
                ->label(__('Check Out Datetime'))
                ->required(false)
               ,
    
            Forms\Components\Select::make('status')
                ->label(__('Status'))
                ->options([
                    'present' => __('Present'),
                    'absent' => __('Absent'),
                    'leave' => __('On Leave'),
                ])
                ->required(),

                   // ساعات العمل
            Forms\Components\TextInput::make('work_hours')
            ->label(__('Work Hours'))
            ->numeric()
            ->required(false),

        // ملاحظات الموظف
        Forms\Components\Textarea::make('notes')
            ->label(__('Notes'))
            ->rows(3),

        // تسجيل ما إذا كان الموظف متأخرًا أم لا
        Forms\Components\Checkbox::make('is_late')
            ->label(__('Is Late')),
        ]);
    }
    

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('employee.first_name')
                ->label(__('Employee'))
                ->searchable(),
    
            Tables\Columns\TextColumn::make('date')
                ->label(__('Date'))
                ->sortable(),
    
                Tables\Columns\TextColumn::make('zone.name')
                ->label(__('Zone'))
                ->searchable(),
                Tables\Columns\TextColumn::make('shift.name')
                ->label(__('Shift'))
                ->searchable(),
                Tables\Columns\TextColumn::make('ismorning')
                ->label(__('Time of Day'))
                ->formatStateUsing(function ($state) {
                    if (is_null($state)) {
                        return __(''); // عرض فارغ إذا كانت القيمة null
                    }
                    return $state ? __('Morning') : __('Evening'); // صباحي أو مسائي
                }),
            
    
            Tables\Columns\TextColumn::make('check_in')
                ->label(__('Check In')),
    
            Tables\Columns\TextColumn::make('check_in_datetime')
                ->label(__('Check In Datetime'))
                ->toggleable(isToggledHiddenByDefault: true),
    
            Tables\Columns\TextColumn::make('check_out')
                ->label(__('Check Out')),
    
            Tables\Columns\TextColumn::make('check_out_datetime')
                ->label(__('Check Out Datetime'))
                ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('status')
                ->label(__('Status'))
                ->getStateUsing(function ($record) {
                    return match ($record->status) {
                        'present' => __('Present'),
                        'absent' => __('Absent'),
                        'leave' => __('On Leave'),
                        default => __('Unknown'),
                    };
                })
                ->colors([
                    'success' => fn ($state) => $state === __('Present'),
                    'danger' => fn ($state) => $state === __('Absent'),
                    'warning' => fn ($state) => $state === __('On Leave'),
                ]),

                Tables\Columns\TextColumn::make('work_hours')
                ->label(__('Work Hours')),

            Tables\Columns\TextColumn::make('notes')
                ->label(__('Notes')),

                Tables\Columns\BadgeColumn::make('is_late')
                ->label(__('Is Late'))
                ->getStateUsing(fn ($record) => $record->is_late ? __('Yes') : __('No'))
                ->colors([
                    'danger' => fn ($state) => $state === __('Yes'),
                    'success' => fn ($state) => $state === __('No')
                ]),
        ])
        ->filters([
         
                Tables\Filters\Filter::make('present_status')
                ->query(fn (Builder $query) => $query->where('status', 'present'))
                ->label(__('Present')),

                SelectFilter::make('shift_id')
                ->label('Shift')
                ->options(Shift::all()->pluck('name', 'id')->toArray()),
              
              
                SelectFilter::make('ismorning')
                ->options([
                    true => 'صباح',
                    false => 'مساء',
                ]),
            
            
            
            
            
            
                SelectFilter::make('status')
    ->label(__('Status'))
    ->options([
        'present' => __('Present'),
        'absent' => __('Absent'),
        'leave' => __('On Leave'),
    ]),
 // فلتر الحالة


// فلتر الموظف
SelectFilter::make('employee_id')
    ->label(__('Employee'))
    ->options(\App\Models\Employee::query()->pluck('first_name', 'id')->toArray())
    ->searchable(),




// فلتر المنطقة
SelectFilter::make('zone_id')
    ->label(__('Zone'))
    ->options(\App\Models\Zone::query()->pluck('name', 'id')->toArray())
    ->searchable(),




// فلتر التاريخ
Filter::make('date_range')
    ->label(__('Date Range'))
    ->form([
        Forms\Components\DatePicker::make('from')->label(__('From')),
        Forms\Components\DatePicker::make('to')->label(__('To')),
    ])
    ->query(function (\Illuminate\Database\Eloquent\Builder $query, $data) {
        if (!empty($data['from'])) {
            $query->where('date', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query->where('date', '<=', $data['to']);
        }
    }),


        ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
