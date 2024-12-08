<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeProjectRecordResource\Pages;
use App\Models\EmployeeProjectRecord;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Zone;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\Action;


use Closure;

class EmployeeProjectRecordResource extends Resource
{
    protected static ?string $model = EmployeeProjectRecord::class;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

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
            Select::make('employee_id')
                ->label(__('Employee'))
                ->options(Employee::all()->pluck('first_name', 'id'))
                ->searchable()
                ->required(),
            
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
                if (!$projectId) {
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
                if (!$zoneId) {
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
                ->required()
            
            
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.first_name')
                    ->label(__('Employee'))
                    ->sortable()
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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
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
}
