<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestResource\Pages;
use App\Filament\Resources\RequestResource\RelationManagers;

use App\Models\Request;
use App\Models\Employee;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}

    public static function getNavigationLabel(): string
{
    return __('Requests');
}

public static function getPluralLabel(): string
{
    return __('Requests');
}

public static function getNavigationGroup(): ?string
{
    return __('Request Management');
}


    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->label(__('Type'))
                ->options([
                    'leave' => __('Leave Request'),
                    'transfer' => __('Transfer Request'),
                    'compensation' => __('Compensation Request'),
                ])
                ->required(),

            Forms\Components\Select::make('submitted_by')
                ->label(__('Submitted By'))
                ->options(User::all()->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\Select::make('employee_id')
                ->label(__('Employee'))
                ->options(Employee::all()->pluck('first_name', 'id'))
                ->searchable()
                ->nullable(),

            Forms\Components\Textarea::make('description')
                ->label(__('Description')),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->label(__('Type')),
                Tables\Columns\TextColumn::make('submittedBy.name')->label(__('Submitted By')),
                Tables\Columns\TextColumn::make('employee.first_name')->label(__('Employee')),
                Tables\Columns\TextColumn::make('status')->label(__('Status')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options([
                        'leave' => __('Leave Request'),
                        'transfer' => __('Transfer Request'),
                        'compensation' => __('Compensation Request'),
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'pending' => __('Pending'),
                        'approved' => __('Approved'),
                        'rejected' => __('Rejected'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'edit' => Pages\EditRequest::route('/{record}/edit'),
        ];
    }
    public static function getRelations(): array
{
    return [
        RelationManagers\RequestApprovalRelationManager::class,
    ];
}

}
