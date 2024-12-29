<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Policy;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PolicyResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PolicyResource\RelationManagers;

class PolicyResource extends Resource
{
    protected static ?string $model = Policy::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
        public static function getNavigationLabel(): string
    {
        return __('Policies');
    }
    
    public static function getPluralLabel(): string
    {
        return __('Policies');
    }
    
    public static function getNavigationGroup(): ?string
    {
        return __('Request Management');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('policy_name')
                ->label('اسم السياسة')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('policy_type')
                ->label('نوع السياسة')
                ->required()
                ->maxLength(255),
                Forms\Components\Select::make('request_type_id')
    ->label(__('Request Type'))
    ->options(\App\Models\RequestType::all()->pluck('name', 'id'))
    ->searchable()
    ->required(),
            Forms\Components\Textarea::make('description')
                ->label('وصف السياسة')
                ->maxLength(500),
            Forms\Components\Textarea::make('conditions')
                ->label('شروط السياسة')
                ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('policy_name')->label('اسم السياسة'),
                Tables\Columns\TextColumn::make('policy_type')->label('نوع السياسة'),
                Tables\Columns\TextColumn::make('description')->label('وصف السياسة'),
                Tables\Columns\TextColumn::make('conditions')->label('شروط السياسة'),
           
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListPolicies::route('/'),
            'create' => Pages\CreatePolicy::route('/create'),
            'edit' => Pages\EditPolicy::route('/{record}/edit'),
        ];
    }
}
