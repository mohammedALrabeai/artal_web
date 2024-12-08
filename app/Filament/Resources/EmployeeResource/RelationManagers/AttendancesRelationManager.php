<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

class AttendanceRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances'; // العلاقة مع الحضور والانصراف

    protected static ?string $recordTitleAttribute = 'date'; // العمود الذي يظهر في عنوان السجلات

    // تعريف النموذج
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')
                ->label(__('Date'))
                ->required(),

            Forms\Components\TimePicker::make('check_in_time')
                ->label(__('Check-in Time'))
                ->required(),

            Forms\Components\TimePicker::make('check_out_time')
                ->label(__('Check-out Time'))
                ->nullable(),

            Forms\Components\Textarea::make('notes')
                ->label(__('Notes'))
                ->nullable(),
        ]);
    }

    // تعريف الجدول
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label(__('Date'))
                    ->date()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('check_in_time')
                    ->label(__('Check-in Time')),

                Tables\Columns\TextColumn::make('check_out_time')
                    ->label(__('Check-out Time')),

                    Tables\Columns\TextColumn::make('check_out')
                    ->label(__('Check Out')),
                    Tables\Columns\BadgeColumn::make('status')
                    ->label(__('Status'))
                    ->getStateUsing(function ($record) {
                        return match ($record->status) {
                            'present' => __('Present'),
                            'absent' => __('Absent'),
                            'leave' => __('On Leave'),
                            default => __('Unknown'),
                        };
                    })
                    ->colors([
                        'success' => fn ($state) => $state === __('Present'),
                        'danger' => fn ($state) => $state === __('Absent'),
                        'warning' => fn ($state) => $state === __('On Leave'),
                    ]),

                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(50),
            ])
            ->filters([
                // يمكنك إضافة فلاتر هنا لتصفية الحضور حسب التاريخ أو حالة الحضور.
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
}
