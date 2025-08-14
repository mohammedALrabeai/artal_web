<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoverageResource\Pages;
use App\Models\Coverage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CoverageResource extends Resource
{
    protected static ?string $model = Coverage::class;

    protected static ?int $navigationSort = 3; // ترتيب في لوحة التحكم

    public static function getNavigationBadge(): ?string
    {
        // ✅ إخفاء العدد عن المستخدمين غير الإداريين
        if (! auth()->user()?->hasRole('admin')) {
            return null;
        }

        return static::getModel()::count();
    }

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('Coverages');
    }

    public static function getPluralLabel(): string
    {
        return __('Coverages');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Employee Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('employee_id')
                ->label(__('Covering Employee'))
                ->relationship('employee', 'first_name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Select::make('absent_employee_id')
                ->label(__('Absent Employee'))
                ->relationship('absentEmployee', 'first_name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Select::make('zone_id')
                ->label(__('Zone'))
                ->relationship('zone', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\DatePicker::make('date')
                ->label(__('Date'))
                ->required(),

            Forms\Components\Select::make('status')
                ->label(__('Status'))
                ->options([
                    'completed' => __('Completed'),
                    'cancelled' => __('Cancelled'),
                ])
                ->required(),

            Forms\Components\Select::make('added_by')
                ->label(__('Added By'))
                ->relationship('addedBy', 'name')
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')
                ->label(__('ID'))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            // Tables\Columns\TextColumn::make('employee.first_name')
            //     ->label(__('Covering Employee'))
            //     ->sortable()
            //     ->searchable()
            //     ->toggleable(),
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

            // Tables\Columns\TextColumn::make('absentEmployee.first_name')
            //     ->label(__('Absent Employee'))
            //     ->getStateUsing(function ($record) {
            //         return $record->absentEmployee->first_name . ' ' .
            //                $record->absentEmployee->family_name;
            //     })
            //     ->sortable()
            //     ->searchable(),
            // Tables\Columns\TextColumn::make('absentEmployee.full_name')
            //     ->label(__('Absent Employee'))
            //     ->getStateUsing(function ($record) {
            //         return $record->absentEmployee
            //             ? ($record->absentEmployee->first_name.' '.$record->absentEmployee->family_name)
            //             : __('Not Assigned'); // ✅ إذا لم يكن هناك موظف غائب، يظهر "غير مسند"
            //     })
            //     ->sortable()
            //     ->searchable(),
                 Tables\Columns\TextColumn::make('full_name_absent')
                ->label(__('Employee'))
                ->getStateUsing(
                    fn($record) => $record->absentEmployee
                        ? $record->absentEmployee->first_name . ' ' .
                        $record->absentEmployee->father_name . ' ' .
                        $record->absentEmployee->grandfather_name . ' ' .
                        $record->absentEmployee->family_name :""
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
                Tables\Columns\TextColumn::make('absentEmployee.national_id')
                ->label(__('National ID'))
                ->searchable(),

            Tables\Columns\TextColumn::make('zone.name')
                ->label(__('Zone'))
                ->sortable()
                ->searchable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('date')
                ->label(__('Date'))
                ->sortable()
                ->toggleable(),

            Tables\Columns\BadgeColumn::make('status')
                ->label(__('Status'))
                ->colors([
                    'success' => 'completed',
                    'danger' => 'cancelled',
                ])
                ->toggleable(),

            Tables\Columns\TextColumn::make('addedBy.name')
                ->label(__('Added By'))
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('created_at')
                ->label(__('Created At'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('updated_at')
                ->label(__('Updated At'))
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'completed' => __('Completed'),
                        'cancelled' => __('Cancelled'),
                    ]),

                SelectFilter::make('zone_id')
                    ->label(__('Zone'))
                    ->relationship('zone', 'name'),

                SelectFilter::make('employee_id')
                    ->label(__('Covering Employee'))
                    ->relationship('employee', 'first_name'),

                SelectFilter::make('absent_employee_id')
                    ->label(__('Absent Employee'))
                    ->relationship('absentEmployee', 'first_name'),

                Tables\Filters\Filter::make('date_range')
                    ->label(__('Date Range'))
                    ->form([
                        Forms\Components\DatePicker::make('from')->label(__('From')),
                        Forms\Components\DatePicker::make('to')->label(__('To')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['from']) {
                            $query->where('date', '>=', $data['from']);
                        }
                        if ($data['to']) {
                            $query->where('date', '<=', $data['to']);
                        }

                        return $query;
                    }),
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
            'index' => Pages\ListCoverages::route('/'),
            'create' => Pages\CreateCoverage::route('/create'),
            'edit' => Pages\EditCoverage::route('/{record}/edit'),
        ];
    }
}
