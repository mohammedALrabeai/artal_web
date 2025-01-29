<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Model;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('media') // File input for media
                    ->label('Media File')
                    ->required()
                    ->directory('employees') // Save files to 'storage/employees'
                    ->visibility('public') // Ensure public access
                    ->columnSpanFull(), // Full width in the form

                    Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),
             
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name') // Display the file name
                ->label('Name')
                ->sortable()
                ->searchable(), 
                Tables\Columns\TextColumn::make('file_name') // Display the file name
                ->label('file_name')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('size') // File size
                ->label('Size')
                ->formatStateUsing(fn ($state) => round($state / 1024, 2) . ' KB'),
            Tables\Columns\TextColumn::make('mime_type') // File type
                ->label('Type'),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Uploaded At')
                ->dateTime(),
               
            ])
            ->filters([
                // Add filters if necessary
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->using(function (array $data, string $model): Model {
                    $pathToFile =  storage_path('app/public/' . $data['media']);
                    return  $this->getOwnerRecord()->addMedia($pathToFile)
                    ->usingName($data['name'])
                    ->preservingOriginal()
                    ->toMediaCollection();
                }),
                // Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DetachAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DetachBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
