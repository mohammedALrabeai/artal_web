<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Leave;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Forms\Components\EmployeeSelect;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\LeaveResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\LeaveResource\RelationManagers;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;
    public static function getNavigationBadge(): ?string
    {
        // ✅ إخفاء العدد عن المستخدمين غير الإداريين
        if (!auth()->user()?->hasRole('admin')) {
            return null;
        }

        return static::getModel()::count();
    }

    protected static ?int $navigationSort = 2;


    protected static ?string $navigationIcon = 'fluentui-document-edit-24-o';
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
            EmployeeSelect::make(),

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
            Tables\Columns\TextColumn::make('full_name')
                ->label(__('Employee'))
                ->getStateUsing(
                    fn($record) => $record->employee->first_name . ' ' .
                        $record->employee->father_name . ' ' .
                        $record->employee->grandfather_name . ' ' .
                        $record->employee->family_name
                )
                ->searchable(query: function ($query, $search) {
                    return $query->whereHas('employee', function ($subQuery) use ($search) {
                        $subQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('father_name', 'like', "%{$search}%")
                            ->orWhere('grandfather_name', 'like', "%{$search}%")
                            ->orWhere('family_name', 'like', "%{$search}%")
                            // ->orWhere('national_id', 'like', "%{$search}%")
                        ;
                    });
                }),

            Tables\Columns\TextColumn::make('employee.national_id')
                ->label(__('National ID'))
                ->searchable(),

            Tables\Columns\TextColumn::make('leaveType.name')
                ->label(__('Leave Type'))
                ->sortable(),

            Tables\Columns\TextColumn::make('employeeProjectRecordDetails')
                ->label(__('Project'))
                ->getStateUsing(function ($record) {
                    $assignment = $record->employeeProjectRecord;

                    if (! $assignment) return '-';

                    return optional($assignment->project)->name . ' - ' .
                        optional($assignment->zone)->name . ' - ' .
                        optional($assignment->shift)->name;
                })
                ->limit(30)
                ->tooltip(fn($state) => $state),

            Tables\Columns\TextColumn::make('start_date')
                ->label(__('Start Date'))
                ->date(),

            Tables\Columns\TextColumn::make('end_date')
                ->label(__('End Date'))
                ->date()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('reason')
                ->label(__('Reason'))
                ->limit(50)

                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\IconColumn::make('approved')
                ->label(__('Approved'))
                ->boolean(),
            Tables\Columns\TextColumn::make('created_at')
                ->label(__('Created At'))
                ->dateTime()
                ->sortable(),

            Tables\Columns\TextColumn::make('updated_at')
                ->label(__('Updated At'))
                ->dateTime()
                ->sortable()
                ->toggleable(),
        ])

            ->filters([
                // Tables\Filters\SelectFilter::make('type')
                //     ->label(__('Type'))
                //     ->options([
                //         'annual' => __('Annual'),
                //         'sick' => __('Sick'),
                //         'unpaid' => __('Unpaid'),
                //     ]),

                Tables\Filters\Filter::make('approved')
                    ->label(__('Approved'))
                    ->query(fn(Builder $query) => $query->where('approved', true)),
                // فلتر التاريخ
                Tables\Filters\Filter::make('date_range')
                    ->label(__('Date Range'))
                    ->form([
                        Forms\Components\DatePicker::make('from')->label(__('From')),
                        Forms\Components\DatePicker::make('to')->label(__('To')),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, $data) {
                        if (!empty($data['from'])) {
                            $query->where('start_date', '>=', $data['from']);
                        }
                        if (!empty($data['to'])) {
                            $query->where('start_date', '<=', $data['to']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                ExportBulkAction::make()
            ]);


        // فلتر التاريخ

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
