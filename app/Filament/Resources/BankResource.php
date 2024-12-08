<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankResource\Pages;
use App\Filament\Resources\BankResource\RelationManagers;
use App\Models\Bank;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Tables\Filters\Filter;

class BankResource extends Resource
{
    protected static ?string $model = Bank::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';


    public static function getNavigationLabel(): string
    {
        return __('Banks');
    }

    public static function getPluralLabel(): string
    {
        return __('Banks');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Finance Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('Bank Name'))
                ->required(),
            Forms\Components\Textarea::make('address')
                ->label(__('Address')),
            Forms\Components\TextInput::make('contact_number')
                ->label(__('Contact Number')),
            Forms\Components\TextInput::make('email')
                ->label(__('Email')),
            Forms\Components\Textarea::make('notes')
                ->label(__('Notes')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Bank Name')),
                Tables\Columns\TextColumn::make('contact_number')
                    ->label(__('Contact Number')),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime(),
            ])
            ->filters([
                Filter::make('has_loans')
                    ->label(__('Has Loans'))
                    ->query(fn ($query) => $query->whereHas('loans')),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanks::route('/'),
            'create' => Pages\CreateBank::route('/create'),
            'edit' => Pages\EditBank::route('/{record}/edit'),
        ];
    }
}
