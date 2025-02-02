<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Project;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProjectResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\ProjectResource\RelationManagers;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}

    public static function getNavigationLabel(): string
    {
        return __('Projects');
    }
    
    public static function getPluralLabel(): string
    {
        return __('Projects');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('Zone & Project Management');
    }
    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('Name')), // إضافة تسمية مترجمة
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()
                    ->label(__('Description')), // إضافة تسمية مترجمة
                // Forms\Components\TextInput::make('area_id')
                //     ->required()
                //     ->numeric(),
                Forms\Components\Select::make('area_id')
                    ->required()
                    ->options(
                        collect(\App\Models\Area::all())->pluck('name', 'id')
                    )
                    ->placeholder(__('Select Area')) // إضافة تسمية مترجمة
                    ->searchable()
                    ->label(__('Area')), // إضافة تسمية مترجمة
                Forms\Components\DatePicker::make('start_date')
                    ->required()
                    ->label(__('Start Date')), // إضافة تسمية مترجمة
                Forms\Components\DatePicker::make('end_date')
                    ->label(__('End Date')), // إضافة تسمية مترجمة
                Forms\Components\TextInput::make('emp_no')
                    ->label(__('Number of Employees (All shifts included)')) // التسمية موجودة بالفعل
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        
                return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->label(__('Name')), // إضافة تسمية مترجمة
                    Tables\Columns\TextColumn::make('area_id')
                        ->numeric()
                        ->sortable()
                        ->label(__('Area ID')), // إضافة تسمية مترجمة
                    Tables\Columns\TextColumn::make('start_date')
                        ->date()
                        ->sortable()
                        ->label(__('Start Date')), // إضافة تسمية مترجمة
                    Tables\Columns\TextColumn::make('end_date')
                        ->date()
                        ->sortable()
                        ->label(__('End Date')), // إضافة تسمية مترجمة
                    Tables\Columns\TextColumn::make('emp_no')
                        ->label(__('Number of Employees')) // التسمية موجودة بالفعل
                        ->sortable(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->label(__('Created At')), // إضافة تسمية مترجمة
                    Tables\Columns\TextColumn::make('updated_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->label(__('Updated At')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label(__('Delete Selected')), // إضافة تسمية مترجمة
                ]),
                ExportBulkAction::make()
                    ->label(__('Export')),
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
