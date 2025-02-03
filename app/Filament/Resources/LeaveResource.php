<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Leave;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\LeaveResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\LeaveResource\RelationManagers;
use App\Models\Employee;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;
    public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}
protected static ?int $navigationSort = 2; 


    protected static ?string $navigationIcon = 'fluentui-document-edit-24-o';
    public static function getNavigationLabel(): string
    {
        return __('Leaves');
    }
    
    public static function getPluralLabel(): string
    {
        return __('Leaves');
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
            ->searchable()
            ->placeholder(__('Search for an employee...'))
           
            ->getSearchResultsUsing(function (string $search) {
                return \App\Models\Employee::query()
                    ->where('national_id', 'like', "%{$search}%") // البحث باستخدام رقم الهوية
                    ->orWhere('first_name', 'like', "%{$search}%") // البحث باستخدام الاسم الأول
                    ->orWhere('family_name', 'like', "%{$search}%") // البحث باستخدام اسم العائلة
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(function ($employee) {
                        return [
                            $employee->id => "{$employee->first_name} {$employee->family_name} ({$employee->id})"
                        ]; // عرض الاسم الأول، العائلة، والمعرف
                    });
            })
            ->getOptionLabelUsing(function ($value) {
                $employee = \App\Models\Employee::find($value);
                return $employee
                    ? "{$employee->first_name} {$employee->family_name} ({$employee->id})" // عرض الاسم والمعرف عند الاختيار
                    : null;
            })
            ->preload()
            ->required(),
            
            Forms\Components\DatePicker::make('start_date')
                ->label(__('Start Date'))
                ->required(),
    
            Forms\Components\DatePicker::make('end_date')
                ->label(__('End Date'))
                ->required(),
    
            Forms\Components\Select::make('type')
                ->label(__('Type'))
                ->options([
                    'annual' => __('Annual'),
                    'sick' => __('Sick'),
                    'unpaid' => __('Unpaid'),
                ])
                ->required(),
    
            Forms\Components\Textarea::make('reason')
                ->label(__('Reason')),
    
            Forms\Components\Toggle::make('approved')
                ->label(__('Approved')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('employee.first_name')
                ->label(__('Employee'))
                ->searchable(),
    
            Tables\Columns\TextColumn::make('start_date')
                ->label(__('Start Date'))
                ->date(),

            Tables\Columns\TextColumn::make('end_date')
                ->label(__('End Date'))
                ->date()
                ->toggleable(isToggledHiddenByDefault: true),
            
            Tables\Columns\TextColumn::make('reason')
                ->label(__('Reason'))
                ->toggleable(isToggledHiddenByDefault: true),
          
    
                Tables\Columns\BadgeColumn::make('type')
                ->label(__('Type'))
                ->getStateUsing(function ($record) {
                    // ترجمة النصوص مباشرة
                    return match ($record->type) {
                        'annual' => __('Annual'),
                        'sick' => __('Sick'),
                        'unpaid' => __('Unpaid'),
                        default => __('Unknown'),
                    };
                })
                ->colors([
                    'success' => fn ($state) => $state === __('Annual'),
                    'danger' => fn ($state) => $state === __('Sick'),
                    'warning' => fn ($state) => $state === __('Unpaid'),
                ]),
            
    
            Tables\Columns\BooleanColumn::make('approved')
                ->label(__('Approved')),
        ])
    
            ->filters([
                Tables\Filters\SelectFilter::make('type')
        ->label(__('Type'))
        ->options([
            'annual' => __('Annual'),
            'sick' => __('Sick'),
            'unpaid' => __('Unpaid'),
        ]),

    Tables\Filters\Filter::make('approved')
        ->label(__('Approved'))
        ->query(fn (Builder $query) => $query->where('approved', true)),
        // فلتر التاريخ
 Tables\Filters\Filter::make('date_range')
->label(__('Date Range'))
->form([
    Forms\Components\DatePicker::make('from')->label(__('From')),
    Forms\Components\DatePicker::make('to')->label(__('To')),
])
->query(function (\Illuminate\Database\Eloquent\Builder $query, $data) {
    if (!empty($data['from'])) {
        $query->where('start_date', '>=', $data['from']);
    }
    if (!empty($data['to'])) {
        $query->where('start_date', '<=', $data['to']);
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
                ExportBulkAction::make()
            ]);


            // فلتر التاريخ

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
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
        ];
    }
}
