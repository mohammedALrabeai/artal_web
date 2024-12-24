<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ResignationsRelationManager extends RelationManager
{
    protected static string $relationship = 'resignations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('resignation_date')
                    ->label('Resignation Date')
                    ->required(),

                Forms\Components\TextInput::make('reason')
                    ->label('Reason'),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('resignation_date')
            ->columns([
                Tables\Columns\TextColumn::make('resignation_date')
                    ->label('Resignation Date')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->searchable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
