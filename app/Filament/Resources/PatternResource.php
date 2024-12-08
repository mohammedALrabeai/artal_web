<?php
namespace App\Filament\Resources;

use App\Models\Pattern;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Resources\PatternResource\Pages;

class PatternResource extends Resource
{
    protected static ?string $model = Pattern::class;
    public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}


    // protected static ?string $navigationIcon = 'heroicon-o-collection'; // أيقونة المورد

    public static function getNavigationLabel(): string
    {
        return __('Patterns');
    }
    
    public static function getPluralLabel(): string
    {
        return __('Patterns');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('Pattern Management');
    }
    

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('Name')) // اسم الحقل مترجم
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('working_days')
                ->label(__('Working Days'))
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('off_days')
                ->label(__('Off Days'))
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('hours_cat')
                ->label(__('Hours Category'))
                ->numeric()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('working_days')
                    ->label(__('Working Days'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('off_days')
                    ->label(__('Off Days'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('hours_cat')
                    ->label(__('Hours Category'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('working_days')
                    ->label(__('Working Days'))
                    ->options(range(1, 7)), // خيارات من 1 إلى 7 أيام

                TernaryFilter::make('off_days')
                    ->label(__('Has Off Days'))
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatterns::route('/'),
            'create' => Pages\CreatePattern::route('/create'),
            'edit' => Pages\EditPattern::route('/{record}/edit'),
        ];
    }
}
