<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Exclusion;
use Filament\Tables\Table;
use App\Enums\ExclusionType;
use Filament\Resources\Resource;
use App\Forms\Components\EmployeeSelect;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ExclusionResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ExclusionResource\RelationManagers;

class ExclusionResource extends Resource
{
    protected static ?string $model = Exclusion::class;

    protected static ?string $navigationIcon = 'fluentui-document-dismiss-16-o';


    protected static ?int $navigationSort = 3; // ترتيب في لوحة التحكم
    public static function getNavigationBadge(): ?string
    {
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
                            ->mapWithKeys(fn($type) => [$type->value => $type->label()])
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
                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label(__('Employee'))
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
            ])
            ->filters([])
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
            'index' => Pages\ListExclusions::route('/'),
            'create' => Pages\CreateExclusion::route('/create'),
            'edit' => Pages\EditExclusion::route('/{record}/edit'),
        ];
    }
}
