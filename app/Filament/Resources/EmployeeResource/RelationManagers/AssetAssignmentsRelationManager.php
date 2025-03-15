<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class AssetAssignmentsRelationManager extends RelationManager
{
    // اسم العلاقة في موديل Employee
    protected static string $relationship = 'assetAssignments';

    // العهد
    protected static ?string $title = 'العُهد';

    // اختيار الحقل الذي يظهر كعنوان للسجل داخل الجدول (يمكن تعديله حسب الحاجة)
    protected static ?string $recordTitleAttribute = 'asset_id';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Select::make('asset_id')
                ->label(__('Asset'))
                ->relationship('asset', 'asset_name')
                ->required()
                ->searchable(),
            DatePicker::make('assigned_date')
                ->label(__('Assigned Date'))
                ->required()
                ->default(now()),
            DatePicker::make('expected_return_date')
                ->label(__('Expected Return Date'))
                ->placeholder('Select expected return date'),
            DatePicker::make('returned_date')
                ->label(__('Returned Date'))
                ->placeholder('Select returned date'),
            TextInput::make('condition_at_assignment')
                ->label(__('Condition at Assignment'))
                ->placeholder('Enter condition at assignment')
                ->required(),
            TextInput::make('condition_at_return')
                ->label(__('Condition at Return'))
                ->placeholder('Enter condition at return'),
            Textarea::make('notes')
                ->label(__('Notes'))
                ->placeholder('Enter any additional notes')
                ->rows(3)
                ->maxLength(500),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('asset.asset_name')
                    ->label(__('Asset'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('assigned_date')
                    ->label(__('Assigned Date'))
                    ->date(),
                TextColumn::make('expected_return_date')
                    ->label(__('Expected Return Date'))
                    ->date(),
                TextColumn::make('returned_date')
                    ->label(__('Returned Date'))
                    ->date(),
                TextColumn::make('condition_at_assignment')
                    ->label(__('Condition at Assignment'))
                    ->limit(50),
                TextColumn::make('condition_at_return')
                    ->label(__('Condition at Return'))
                    ->limit(50),
            ])
            ->filters([
                // يمكن إضافة فلاتر إضافية هنا حسب الحاجة
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label(__('Create')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label(__('Edit')),
                Tables\Actions\DeleteAction::make()->label(__('Delete')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label(__('Delete')),
            ]);
    }
}
