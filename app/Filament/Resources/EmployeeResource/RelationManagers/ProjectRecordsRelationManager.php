<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use App\Models\EmployeeProjectRecord;
use App\Models\Project;
use App\Models\Zone;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;

class ProjectRecordsRelationManager extends RelationManager // تحديث الاسم
{
    protected static string $relationship = 'projectRecords'; // اسم العلاقة // اسم العلاقة في موديل الموظف

    protected static ?string $recordTitleAttribute = 'project.name';

    public  function canCreate(): bool
{
    return true; // تأكد من السماح بإضافة سجلات جديدة
}


    public  function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('project_id')
                ->label(__('Project'))
                ->options(Project::all()->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, $set) {
                    $set('zone_id', null); // إعادة تعيين الحقل عند تغيير المشروع
                }),

            Forms\Components\Select::make('zone_id')
                ->label(__('Zone'))
                ->options(function ($get) {
                    $projectId = $get('project_id');
                    return $projectId
                        ? Zone::where('project_id', $projectId)->pluck('name', 'id')
                        : [];
                })
                ->searchable()
                ->required(),

            Forms\Components\DatePicker::make('start_date')
                ->label(__('Start Date'))
                ->required(),

            Forms\Components\DatePicker::make('end_date')
                ->label(__('End Date'))
                ->nullable(),

            Forms\Components\Toggle::make('status')
                ->label(__('Active'))
                ->default(true),
        ]);
    }

    public  function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label(__('Project'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('zone.name')
                    ->label(__('Zone'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('Start Date'))
                    ->date(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('End Date'))
                    ->date(),

                Tables\Columns\BooleanColumn::make('status')
                    ->label(__('Active')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label(__('Project'))
                    ->options(Project::all()->pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('zone_id')
                    ->label(__('Zone'))
                    ->options(Zone::all()->pluck('name', 'id')),

                Tables\Filters\TernaryFilter::make('status')
                    ->label(__('Active')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getHeaderActions(): array
    {
        return [
            CreateAction::make()
            ->label(__('Add Record'))
            ->icon('heroicon-o-plus'),
        
        ];
    }
}
