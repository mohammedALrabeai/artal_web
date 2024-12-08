<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;





class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $recordTitleAttribute = 'type';

    public  function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->label(__('Type'))
                ->options([
                    'text' => __('Text'),
                    'link' => __('Link'),
                    'image' => __('Image'),
                    'video' => __('Video'),
                    'file' => __('File'),
                ])
                ->required(),
            Forms\Components\TextInput::make('content')
                ->label(__('Content'))
                ->required(),
            Forms\Components\DatePicker::make('expiry_date')
                ->label(__('Expiry Date')),
            Forms\Components\Textarea::make('notes')
                ->label(__('Notes')),
        ]);
    }

    public  function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type')),
                Tables\Columns\TextColumn::make('content')
                    ->label(__('Content')),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label(__('Expiry Date'))
                    ->date(),
                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Notes')),
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
