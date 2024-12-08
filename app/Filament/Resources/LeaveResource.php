<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Filament\Resources\LeaveResource\RelationManagers;
use App\Models\Leave;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;
    public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}


    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
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
                ->options(\App\Models\Employee::all()->pluck('first_name', 'id'))
                ->searchable()
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
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
        ];
    }
}
