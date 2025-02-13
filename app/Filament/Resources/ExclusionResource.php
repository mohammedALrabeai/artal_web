<?php

namespace App\Filament\Resources;

use App\Enums\ExclusionType;
use App\Filament\Resources\ExclusionResource\Pages;
use App\Forms\Components\EmployeeSelect;
use App\Models\Exclusion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExclusionResource extends Resource
{
    protected static ?string $model = Exclusion::class;

    protected static ?string $navigationIcon = 'fluentui-document-dismiss-16-o';

    protected static ?int $navigationSort = 3; // ترتيب في لوحة التحكم

    public static function getNavigationBadge(): ?string
    {
        // ✅ إخفاء العدد عن المستخدمين غير الإداريين
        if (! auth()->user()?->hasRole('admin')) {
            return null;
        }

        return static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return __('Exclusion');
    }

    public static function getPluralLabel(): string
    {
        return __('Exclusions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Employee Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                EmployeeSelect::make(),

                Forms\Components\Select::make('type')
                    ->label(__('Exclusion Type'))
                    ->options(
                        collect(ExclusionType::cases())
                            ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
                            ->toArray()
                    )
                    ->required(),

                Forms\Components\DatePicker::make('exclusion_date')
                    ->label(__('Exclusion Date'))
                    ->required(),

                Forms\Components\Textarea::make('reason')
                    ->label(__('Reason'))
                    ->nullable(),

                Forms\Components\FileUpload::make('attachment')
                    ->label(__('Attachment'))
                    ->nullable(),

                Forms\Components\Textarea::make('notes')
                    ->label(__('Notes'))
                    ->nullable(),

                // Forms\Components\Select::make('status')
                // ->label(__('Status'))
                // ->options(\App\Models\Exclusion::getStatuses())
                // ->default(\App\Models\Exclusion::STATUS_PENDING) // تعيين القيمة الافتراضية
                // ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('Employee'))
                    ->getStateUsing(fn ($record) => $record->employee->first_name.' '.
                        $record->employee->father_name.' '.
                        $record->employee->grandfather_name.' '.
                        $record->employee->family_name
                    )
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('employee', function ($subQuery) use ($search) {
                            $subQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('father_name', 'like', "%{$search}%")
                                ->orWhere('grandfather_name', 'like', "%{$search}%")
                                ->orWhere('family_name', 'like', "%{$search}%")
                                ->orWhere('national_id', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('employee.national_id')
                    ->label(__('National ID'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Exclusion Type'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('exclusion_date')
                    ->label(__('Exclusion Date'))
                    ->date(),

                Tables\Columns\TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label(__('Status'))
                    ->colors([
                        'primary' => 'Pending',
                        'success' => 'Approved',
                        'danger' => 'Rejected',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListExclusions::route('/'),
            'create' => Pages\CreateExclusion::route('/create'),
            'edit' => Pages\EditExclusion::route('/{record}/edit'),
            'view' => Pages\ViewExclusion::route('/{record}/view'),
        ];
    }
}
