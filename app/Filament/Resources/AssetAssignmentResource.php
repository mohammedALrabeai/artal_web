<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssetAssignmentResource\Pages;
use App\Forms\Components\EmployeeSelect;
use App\Models\AssetAssignment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetAssignmentResource extends Resource
{
    protected static ?string $model = AssetAssignment::class;

    // protected static ?string $navigationIcon = 'heroicon-o-collection';

    // نصوص الواجهة بالإنجليزية مباشرة

    protected static ?string $modelLabel = 'Asset Assignment';

    public static function getNavigationLabel(): string
    {
        return __('Asset Assignments');
    }

    public static function getPluralLabel(): string
    {
        return __('Asset Assignments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Assets Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('asset_id')
                ->label(__('Asset'))
                ->relationship('asset', 'asset_name')
                ->searchable()
                ->preload()
                ->required(),
            EmployeeSelect::make(),
            DatePicker::make('assigned_date')
                ->label(__('Assigned Date'))
                ->default(now()),
            DatePicker::make('expected_return_date')
                ->label(__('Expected Return Date'))
                ->placeholder('Select expected return date'),
            DatePicker::make('returned_date')
                ->label(__('Returned Date'))
                ->placeholder('Select returned date'),
            TextInput::make('condition_at_assignment')
                ->label(__('Condition at Assignment'))
                ->placeholder('Enter condition at assignment'),
            TextInput::make('condition_at_return')
                ->label(__('Condition at Return'))
                ->placeholder('Enter condition at return'),
            Textarea::make('notes')
                ->label(__('Notes'))
                ->placeholder('Enter any additional notes')
                ->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('asset.asset_name')
                ->label(__('Asset'))
                ->sortable()
                ->searchable(),
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
                }),
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
                ->label(__('Condition at Assignment')),
        ])
            ->filters([
                // يمكن إضافة فلاتر إضافية هنا
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label(__('Edit')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label(__('Delete')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // إضافة مديري العلاقات (Relation Managers) إن وُجدت
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetAssignments::route('/'),
            'create' => Pages\CreateAssetAssignment::route('/create'),
            'edit' => Pages\EditAssetAssignment::route('/{record}/edit'),
        ];
    }
}
