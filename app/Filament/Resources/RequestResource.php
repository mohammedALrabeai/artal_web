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
            // Forms\Components\Select::make('type')
            //     ->label(__('Type'))
            //     ->options([
            //         'leave' => __('Leave Request'),
            //         'transfer' => __('Transfer Request'),
            //         'compensation' => __('Compensation Request'),
            //     ])
            //     ->required(),
            Forms\Components\Select::make('type')
            ->label(__('Type'))
            ->options(\App\Models\RequestType::all()->pluck('name', 'key'))
            ->required()
            ->reactive(),

        Forms\Components\Select::make('employee_id')
            ->label(__('Employee'))
            ->options(Employee::all()->pluck('first_name', 'id'))
            ->searchable()
            ->nullable()
            ->required(),


            Forms\Components\Select::make('submitted_by')
                ->label(__('Submitted By'))
                ->options(User::all()->pluck('name', 'id'))
                ->searchable()
                ->required(),

        

            Forms\Components\Textarea::make('description')
                ->label(__('Description')),

        // حقول ديناميكية بناءً على نوع الطلب
        Forms\Components\TextInput::make('duration')
        ->label(__('Duration (Days)'))
        ->visible(fn ($livewire) => $livewire->data['type'] === 'leave')
        ->numeric(),

    Forms\Components\TextInput::make('amount')
        ->label(__('Amount'))
        ->visible(fn ($livewire) => $livewire->data['type'] === 'loan')
        ->numeric(),

    Forms\Components\KeyValue::make('additional_data')
        ->label(__('Additional Data'))
        ->visible(fn ($livewire) => in_array($livewire->data['type'], ['compensation', 'transfer', 'overtime']))
        ->keyLabel(__('Key'))
        ->valueLabel(__('Value')),
                Forms\Components\TextInput::make('leave_balance')
    ->label(__('Leave Balance'))
    ->default(fn($record) => $record ? $record->employee->leave_balance : null)
    ->disabled()
    ->visible(fn($livewire) => $livewire->data['type'] === 'leave')
    ->columnSpan('full'),
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
                Tables\Columns\TextColumn::make('approvalFlows')
                ->label(__('Remaining Levels'))
                ->formatStateUsing(fn($record) => $record->approvalFlows
                    ->where('approval_level', '>', $record->approvals->max('approval_level'))
                    ->map(fn($flow) => __(':role (Level :level)', ['role' => $flow->approver_role, 'level' => $flow->approval_level]))
                    ->join(', '))
                ->sortable(),

                Tables\Columns\TextColumn::make('duration')->label(__('Duration (Days)')),
Tables\Columns\TextColumn::make('amount')->label(__('Amount')),
Tables\Columns\TextColumn::make('additional_data')
    ->label(__('Additional Data'))
    ->formatStateUsing(fn($state) => $state ? json_encode($state) : '-'),
            
            ])
            ->filters([
                // Tables\Filters\SelectFilter::make('type')
                //     ->label(__('Type'))
                //     ->options([
                //         'leave' => __('Leave Request'),
                //         'transfer' => __('Transfer Request'),
                //         'compensation' => __('Compensation Request'),
                //     ]),
                Tables\Filters\SelectFilter::make('type')
    ->label(__('Type'))
    ->options(\App\Models\RequestType::all()->pluck('name', 'key')),

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'pending' => __('Pending'),
                        'approved' => __('Approved'),
                        'rejected' => __('Rejected'),
                    ]),
                    Tables\Filters\Filter::make('my_approvals')
    ->label(__('Requests Awaiting My Approval'))
    ->query(fn($query) => $query->where('current_approver_id', auth()->id()))
    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                ->label(__('Approve'))
                ->action(fn($record, array $data) => $record->approveRequest(auth()->user(), $data['comments']))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->form([
                    Forms\Components\Textarea::make('comments')
                        ->label(__('Comments'))
                        ->required(),
                ])
                ->requiresConfirmation()
                ->hidden(fn($record) => $record->status !== 'pending'),
                Tables\Actions\Action::make('reject')
                ->label(__('Reject'))
                ->action(fn($record, array $data) => $record->rejectRequest(auth()->user(), $data['comments']))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Forms\Components\Textarea::make('comments')
                        ->label(__('Reason for Rejection'))
                        ->required(),
                ])
                ->requiresConfirmation()
                ->hidden(fn($record) => $record->status !== 'pending'),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
        RelationManagers\ApprovalsRelationManager::class,
    ];
}

}
