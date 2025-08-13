<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Asset;
use Filament\Forms\Form;
use App\Enums\AssetStatus;
use Filament\Tables\Table;
use App\Models\AssetAssignment;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use App\Services\AssetTransferService;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use App\Forms\Components\EmployeeSelect;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;






use Illuminate\Support\Facades\DB;

use App\Filament\Resources\AssetAssignmentResource\Pages;



class AssetAssignmentResource extends Resource
{
    protected static ?string $model = AssetAssignment::class;
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
                ->relationship(
                    name: 'asset',
                    titleAttribute: 'asset_name',
                    modifyQueryUsing: function (Builder $query): Builder {
                        return $query
                            ->where('status', AssetStatus::AVAILABLE->value)
                            ->whereDoesntHave('assignments', function (Builder $a) {
                                $a->whereNull('returned_date');
                            });
                    }
                )
                ->searchable()
                ->preload()
                ->required(),

            EmployeeSelect::make()->required(),

            DatePicker::make('assigned_date')
                ->label(__('Assigned Date'))
                ->default(now('Asia/Riyadh'))
                ->required(),

            DatePicker::make('expected_return_date')
                ->label(__('Expected Return Date'))
                ->placeholder('Select expected return date'),

            DatePicker::make('returned_date')
                ->label(__('Returned Date'))
                ->placeholder('Select returned date'),

            TextInput::make('condition_at_assignment')
                ->label(__('Condition at Assignment')),

            TextInput::make('condition_at_return')
                ->label(__('Condition at Return')),

            Textarea::make('notes')
                ->label(__('Notes'))
                ->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // الأصل + SN (آمن)
                TextColumn::make('asset_name')
                    ->label(__('Asset'))
                    ->state(fn($record) => $record?->asset?->asset_name ?? '-')
                    ->description(
                        fn($record) => $record?->asset?->serial_number ? ('SN: ' . $record->asset->serial_number) : null,
                        position: 'below'
                    )
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $like = "%{$search}%";
                        return $query->whereHas('asset', function (Builder $sub) use ($like) {
                            $sub->where('asset_name', 'like', $like)
                                ->orWhere('serial_number', 'like', $like);
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->leftJoin('assets', 'asset_assignments.asset_id', '=', 'assets.id')
                            ->orderBy('assets.asset_name', $direction)
                            ->select('asset_assignments.*');
                    }),

                // اسم الموظف (آمن)
                TextColumn::make('full_name')
                    ->label(__('Employee'))
                    ->state(function ($record) {
                        if (! $record?->employee) return '-';
                        $parts = [
                            $record->employee->first_name,
                            $record->employee->father_name,
                            $record->employee->grandfather_name,
                            $record->employee->family_name,
                        ];
                        return trim(implode(' ', array_filter($parts)));
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $like = "%{$search}%";
                        return $query->whereHas('employee', function (Builder $sub) use ($like) {
                            $sub->where('first_name', 'like', $like)
                                ->orWhere('father_name', 'like', $like)
                                ->orWhere('grandfather_name', 'like', $like)
                                ->orWhere('family_name', 'like', $like)
                                ->orWhere('national_id', 'like', $like);
                        });
                    }),

                // الهوية (آمن)
                TextColumn::make('employee_national_id')
                    ->label(__('National ID'))
                    ->state(fn($record) => $record?->employee?->national_id ?? '-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('employee', function (Builder $sub) use ($search) {
                            $sub->where('national_id', 'like', "%{$search}%");
                        });
                    }),

                // حالة التعيين الآن
                BadgeColumn::make('status_now')
                    ->label(__('Status'))
                    ->state(fn($record): string => ($record && $record->returned_date) ? 'Returned' : 'Assigned')
                    ->colors([
                        'success' => fn($state) => $state === 'Returned',
                        'warning' => fn($state) => $state === 'Assigned',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Returned',
                        'heroicon-o-arrow-right'  => 'Assigned',
                    ]),

                // التواريخ
                TextColumn::make('assigned_date')->label(__('Assigned Date'))->date()->toggleable(),
                TextColumn::make('expected_return_date')->label(__('Expected Return Date'))->date()->toggleable(),
                TextColumn::make('returned_date')->label(__('Returned Date'))->date()->toggleable(),

                // الحالات والملاحظات
                TextColumn::make('condition_at_assignment')->label(__('Condition at Assignment'))->toggleable(),
                TextColumn::make('condition_at_return')->label(__('Condition at Return'))->toggleable(),

                TextColumn::make('assigned_by_user')
                    ->label(__('Assigned By'))
                    ->state(
                        fn(AssetAssignment $record) =>
                        $record->assignedBy?->name
                            ?? $record->assignedBy?->email
                            ?? ($record->assigned_by_user_id ? ('#' . $record->assigned_by_user_id) : '-')
                    )
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $like = "%{$search}%";
                        return $query->whereHas('assignedBy', function (Builder $sub) use ($like) {
                            $sub->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                    })
                    ->toggleable(),

                TextColumn::make('returned_by_user')
                    ->label(__('Returned By'))
                    ->state(
                        fn(AssetAssignment $record) =>
                        $record->returnedBy?->name
                            ?? $record->returnedBy?->email
                            ?? ($record->returned_by_user_id ? ('#' . $record->returned_by_user_id) : '-')
                    )
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $like = "%{$search}%";
                        return $query->whereHas('returnedBy', function (Builder $sub) use ($like) {
                            $sub->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                    })
                    ->toggleable(),

                TextColumn::make('notes')->label(__('Notes'))->limit(60)->wrap()->toggleable(isToggledHiddenByDefault: true),

                // حالة الأصل المرتبط (آمن)
                BadgeColumn::make('asset_status')
                    ->label(__('Asset Status'))
                    ->state(fn($record) => $record?->asset?->status?->value)
                    ->formatStateUsing(fn($state) => $state ? \App\Enums\AssetStatus::labels()[$state] ?? $state : '-')
                    ->colors([
                        'success' => fn($state) => $state === AssetStatus::AVAILABLE->value,
                        'danger'  => fn($state) => $state === AssetStatus::CHARGED->value,
                        'warning' => fn($state) => in_array($state, [
                            AssetStatus::MAINTENANCE->value,
                            AssetStatus::DAMAGED->value,
                            AssetStatus::LOST->value,
                        ], true),
                        'gray'    => fn($state) => $state === AssetStatus::RETIRED->value,
                    ])
                    ->toggleable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('transfer')
                    ->label(__('Transfer'))
                    ->icon('heroicon-o-arrows-right-left')
                    ->visible(fn($record) => $record instanceof \App\Models\AssetAssignment && $record->returned_date === null)
                    ->form([
                        EmployeeSelect::make('to_employee_id')->label(__('Replacement'))->required(),
                        DatePicker::make('assigned_date')->label(__('New Assigned Date'))->default(now('Asia/Riyadh'))->required(),
                        DatePicker::make('expected_return_date')->label(__('New Expected Return Date')),
                        TextInput::make('condition_at_return')->label(__('Condition at Return (Current)')),
                        TextInput::make('condition_at_assignment')->label(__('Condition at Assignment (New)')),
                        Textarea::make('notes')->label(__('Notes'))->rows(3),
                    ])
                    ->action(function (\App\Models\AssetAssignment $record, array $data) {
                        $service = app(\App\Services\AssetTransferService::class);
                        $service->transfer(
                            openAssignment: $record,
                            toEmployeeId: (int) $data['to_employee_id'],
                            options: [
                                'assigned_date'           => $data['assigned_date'] ?? null,
                                'expected_return_date'    => $data['expected_return_date'] ?? null,
                                'condition_at_return'     => $data['condition_at_return'] ?? null,
                                'condition_at_assignment' => $data['condition_at_assignment'] ?? null,
                                'notes'                   => $data['notes'] ?? null,
                            ]
                        );
                        Notification::make()->title(__('Asset transferred successfully'))->success()->send();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('return_now')
                    ->label(__('Return'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn($record) => $record instanceof \App\Models\AssetAssignment && $record->returned_date === null)
                    ->requiresConfirmation()
                    ->action(function (\App\Models\AssetAssignment $record) {
                        DB::transaction(function () use ($record) {
                            $record->update([
                                'returned_date'       => now('Asia/Riyadh')->toDateString(),
                                'returned_by_user_id' => Auth::id(),
                            ]);
                            $asset = $record->asset()->lockForUpdate()->first();
                            if ($asset) {
                                $asset->status = \App\Enums\AssetStatus::AVAILABLE;
                                $asset->save();
                            }
                        });
                        Notification::make()->title(__('Asset returned'))->success()->send();
                    }),

                Tables\Actions\Action::make('charge_and_retire')
                    ->label(__('Charge from Salary'))
                    ->icon('heroicon-o-banknotes')
                    ->color('danger')
                    ->visible(fn($record) => $record instanceof \App\Models\AssetAssignment && $record->returned_date === null)
                    ->form([
                        Textarea::make('notes')->label(__('Notes'))->rows(3)
                            ->helperText(__('سيتم إغلاق التعيين وتغيير حالة العهدة إلى Charged (غير قابلة للتسليم)')),
                    ])
                    ->requiresConfirmation()
                    ->action(function (\App\Models\AssetAssignment $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'returned_date'       => now('Asia/Riyadh')->toDateString(),
                                'returned_by_user_id' => Auth::id(),
                                'notes'               => trim(($record->notes ? $record->notes . "\n" : '') . ($data['notes'] ?? '')),
                            ]);
                            $asset = $record->asset()->lockForUpdate()->first();
                            if ($asset) {
                                $asset->status = \App\Enums\AssetStatus::CHARGED;
                                $asset->save();
                            }
                        });
                        Notification::make()->title(__('Asset charged and locked from reassignment'))->success()->send();
                    }),

                Tables\Actions\EditAction::make()->label(__('Edit')),
            ])
         
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label(__('Delete')),
            ]);
    }


    public static function getRelations(): array
    {
        return [];
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
