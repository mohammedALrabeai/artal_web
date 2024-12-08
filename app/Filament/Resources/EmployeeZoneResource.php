<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\EmployeeZone;
use Filament\Resources\Resource;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\EmployeeZoneResource\Pages;
use App\Filament\Resources\EmployeeZoneResource\RelationManagers;

class EmployeeZoneResource extends Resource
{
    protected static ?string $model = EmployeeZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}

    public static function getNavigationLabel(): string
    {
        return __('EmployeeZones');
    }
    
    public static function getPluralLabel(): string
    {
        return __('EmployeeZones');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('Zone & Shift Management');
    }
    

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('employee_id')
                ->label(__('Employee'))
                ->relationship('employee', 'first_name')
                ->searchable()
                ->preload() 
                ->required(),
    
            Forms\Components\Select::make('zone_id')
                ->label(__('Zone'))
                ->relationship('zone', 'name')
                ->searchable()
                ->preload() 
                ->required(),
    
            Forms\Components\DatePicker::make('start_date')
                ->label(__('Start Date'))
                ->required(),
    
            Forms\Components\DatePicker::make('end_date')
                ->label(__('End Date'))
                ->nullable(),
    
            Forms\Components\Select::make('status')
                ->label(__('Status'))
                ->options([
                    'active' => __('Active'),
                    'absent' => __('Absent'),
                    'transferred' => __('Transferred'),
                ])
                ->required(),

              
    
            Forms\Components\Select::make('added_by')
                ->label(__('Added By'))
                ->relationship('addedBy', 'name')
                ->disabled(), // المستخدم يتم تحديده تلقائيًا بناءً على تسجيل الدخول
        ]);
    }
    

    public static function table(Table $table): Table
{
    return $table->columns([
        Tables\Columns\TextColumn::make('employee.first_name')
            ->label(__('Employee'))
            ->searchable()
            ->sortable(),

        Tables\Columns\TextColumn::make('zone.name')
            ->label(__('Zone'))
            ->searchable()
            ->sortable(),

        Tables\Columns\TextColumn::make('start_date')
            ->label(__('Start Date'))
            ->sortable(),

        Tables\Columns\TextColumn::make('end_date')
            ->label(__('End Date'))
            ->sortable()
            ->default(__('Not Ended')),

        // Tables\Columns\BadgeColumn::make('status')
        //     ->label(__('Status'))
        //     ->colors([
        //         'success' => 'active',
        //         'danger' => 'absent',
        //         'warning' => 'transferred',
        //     ])
        //     ->sortable(),



        Tables\Columns\BadgeColumn::make('status')
            ->label(__('Status'))
            ->formatStateUsing(fn ($state) => match ($state) {
                'active' => __('Active'),
                'absent' => __('Absent'),
                'transferred' => __('Transferred'),
                default => $state,
            })
            ->colors([
                'success' => 'active',
                'danger' => 'absent',
                'warning' => 'transferred',
            ])
        
        
    
            ->sortable(),

            Tables\Columns\TextColumn::make('addedBy.name')
            ->label(__('Added By'))
            ->sortable(),
        
    ])
    ->filters([
        SelectFilter::make('status')
            ->label(__('Status'))
            ->options([
                'active' => __('Active'),
                'absent' => __('Absent'),
                'transferred' => __('Transferred'),
            ]),
        SelectFilter::make('zone_id')
            ->label(__('Zone'))
            ->relationship('zone', 'name'),
        SelectFilter::make('employee_id')
            ->label(__('Employee'))
            ->relationship('employee', 'first_name'),
    ])
    ->actions([
        Tables\Actions\EditAction::make(),
    ])
    ->bulkActions([
        Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListEmployeeZones::route('/'),
            'create' => Pages\CreateEmployeeZone::route('/create'),
            'edit' => Pages\EditEmployeeZone::route('/{record}/edit'),
        ];
    }

    // public static function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $data['added_by'] = auth()->id();
    //     return $data;
    // }
    
    // public static function mutateFormDataBeforeSave(array $data): array
    // {
    //     $data['added_by'] = auth()->id();
    //     return $data;
    // }
    

}
