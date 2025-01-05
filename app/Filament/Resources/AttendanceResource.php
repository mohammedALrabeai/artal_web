<?php

namespace App\Filament\Resources;


use Filament\Forms;
use Filament\Tables;
use App\Models\Shift;
use App\Models\Employee;
use Filament\Forms\Form;
use App\Models\Attendance;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;

use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AttendanceResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
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
            ->options(Employee::all()->mapWithKeys(function ($employee) {
                return [$employee->id => "{$employee->first_name} {$employee->family_name} ({$employee->id})"];
            }))
            ->required()
            ->searchable(),
    
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
                ->required(false),
    
               Forms\Components\Select::make('status')
               ->label(__('Status'))
               ->options([
                'off' => __('Off'),    // إضافة خيار عطلة
                   'present' => __('Present'),   // إضافة خيار الحضور
                   'coverage' => __('Coverage'), // إضافة خيار التغطية
                   'M'=>__('Morbid'),  // إضافة خيار مرضي Sick
                   'leave' => __('paid leave'),     // إضافة خيار الإجازة
                   'UV' => __('Unpaid leave'),
                   'absent' => __('Absent'),
              
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

            Forms\Components\Toggle::make('is_coverage')
            ->label(__('Coverage Request')),
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
    
                Tables\Columns\BadgeColumn::make('status')
                ->label(__('Status'))
                ->getStateUsing(function ($record) {
                    return match ($record->status) {
                        'off' => __('Off'),
                        'present' => __('Present'),
                        'coverage' => __('Coverage'),
                        'M' => __('Morbid'),
                        'leave' => __('Paid Leave'),
                        'UV' => __('Unpaid Leave'),
                        'absent' => __('Absent'),
                        default => __('Unknown'),
                    };
                })
                ->colors([
                    'success' => fn ($state) => $state === __('Off'), // أخضر
                    'primary' => fn ($state) => $state === __('Present'), // أزرق فاتح
                    'warning' => fn ($state) => $state === __('Coverage'), // برتقالي
                    'secondary' => fn ($state) => $state === __('Morbid'), // رمادي
                    'blue-dark' => fn ($state) => $state === __('Paid Leave'), // أزرق غامق
                    'orange-dark' => fn ($state) => $state === __('Unpaid Leave'), // برتقالي غامق
                    'danger' => fn ($state) => $state === __('Absent'), // أحمر
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
                Tables\Columns\BadgeColumn::make('approval_status')
                ->label(__('Approval Status'))
                ->formatStateUsing(fn (string $state): string => ucfirst($state)) // تنسيق النص
                ->colors([
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                ]),

                Tables\Columns\BooleanColumn::make('is_coverage')
                    ->label(__('Coverage Request')),   
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
        'coverage' => __('Coverage'),
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

                Tables\Actions\Action::make('Approve')
                ->label(__('Approve'))
                ->form([
                    Forms\Components\Select::make('absent_employee_id')
                        ->label(__('Select Absent Employee'))
                        ->options(\App\Models\Employee::pluck('first_name', 'id')) // جلب قائمة الموظفين
                        ->searchable() // إضافة خاصية البحث في القائمة
                        ->required(),
                ])
                ->visible(fn ($record) => $record->status === 'coverage' && $record->approval_status === 'pending') // إظهار الزر فقط عند status = coverage
                ->action(function ($record, array $data) {
                    // تحديث حالة الطلب إلى "موافق عليه"
                    $record->update(['approval_status' => 'approved']);
            
                    // إضافة سجل في جدول التغطيات
                    $coverage =  \App\Models\Coverage::create([
                        'employee_id' => $record->employee_id, // معرف الموظف الذي قام بالتغطية
                        'absent_employee_id' => $data['absent_employee_id'], // معرف الموظف الغائب
                        'zone_id' => $record->zone_id, // استنتاج معرف الزون من السجل
                        'date' => $record->date, // استنتاج التاريخ من السجل
                        'status' => 'completed',
                        'added_by' => auth()->id(), // المستخدم الحالي هو من وافق
                    ]);
            
                    // تحديث معرف التغطية في الحضور
                    $record->update(['coverage_id' => $coverage->id]);
                })
                ->modalHeading(__('Approve Coverage'))
                ->modalSubmitActionLabel(__('Approve'))
                ->modalCancelActionLabel(__('Cancel')),
            
            
            Tables\Actions\Action::make('Reject')
                ->label(__('Reject'))
                ->visible(fn ($record) => $record->status === 'coverage' && $record->approval_status === 'pending') // الشرط لإظهار الزر
                ->action(fn ($record) => $record->update(['approval_status' => 'rejected'])),
             
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
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
