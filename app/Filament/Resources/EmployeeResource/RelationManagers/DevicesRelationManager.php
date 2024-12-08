<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DevicesRelationManager extends RelationManager
{
    protected static string $relationship = 'devices';

    protected static ?string $recordTitleAttribute = 'device_id'; // عنوان الجهاز في الجدول

    public  function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('device_id')
                ->label(__('Device ID'))
                ->required()
                ->disabled(),

            Forms\Components\Select::make('status')
                ->label(__('Status'))
                ->options([
                    'approved' => __('Approved'),
                    'pending' => __('Pending'),
                    'rejected' => __('Rejected'),
                ])
                ->required(),
        ]);
    }

    public  function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('device_id')
                    ->label(__('Device ID'))
                    ->searchable(),
                    

                    BadgeColumn::make('status')
                        ->label(__('Status'))
                        ->colors([
                            'success' => 'approved', // لون أخضر للحالة المعتمدة
                            'warning' => 'pending', // لون أصفر للحالة المعلقة
                            'danger' => 'rejected', // لون أحمر للحالة المرفوضة
                        ])
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'approved' => __('Approved'),
                            'pending' => __('Pending'),
                            'rejected' => __('Rejected'),
                            default => $state,
                        })
                        ->sortable(),
                

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Added On'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'approved' => __('Approved'),
                        'pending' => __('Pending'),
                        'rejected' => __('Rejected'),
                    ]),
            ])
            ->actions([
                Action::make('toggle_status')
                ->label(fn (Model $record) => $record->status === 'approved' ? __('Reject') : __('Approve'))
                ->icon(fn (Model $record) => $record->status === 'approved' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn (Model $record) => $record->status === 'approved' ? 'danger' : 'success')
                ->action(function (Model $record) {
                    $record->update([
                        'status' => $record->status === 'approved' ? 'pending' : 'approved',
                    ]);
                })
                ->requiresConfirmation() // تأكيد العملية قبل التنفيذ
                ->visible(fn (Model $record) => $record->status === 'approved' || $record->status === 'pending'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
